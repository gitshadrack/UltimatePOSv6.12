<?php

namespace Twf\Pps;

class DurablePrintQueue
{
    private string $queueRoot;
    private string $pendingDirectory;
    private string $statusDirectory;
    private $logger;

    public function __construct(?string $queueRoot = null, ?callable $logger = null)
    {
        $this->queueRoot = $queueRoot ?: dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'queue';
        $this->pendingDirectory = $this->queueRoot.DIRECTORY_SEPARATOR.'pending';
        $this->statusDirectory = $this->queueRoot.DIRECTORY_SEPARATOR.'status';
        $this->logger = $logger;

        foreach ([$this->queueRoot, $this->pendingDirectory, $this->statusDirectory] as $directory) {
            if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
                throw new \RuntimeException('Unable to create print queue directory: '.$directory);
            }
        }
    }

    public function createJobId(): string
    {
        return 'receipt-'.bin2hex(random_bytes(16));
    }

    public function enqueue(string $jobId, object $payload): array
    {
        $jobId = $this->validateJobId($jobId);
        $existing = $this->getStatus($jobId);
        if ($existing['status'] !== 'unknown') {
            return $existing;
        }

        $job = [
            'version' => 1,
            'job_id' => $jobId,
            'status' => 'queued',
            'attempts' => 0,
            'created_at' => time(),
            'updated_at' => time(),
            'next_attempt_at' => time(),
            'last_error' => null,
            'payload' => $payload,
        ];

        $this->writeJson($this->pendingPath($jobId), $job);
        $this->log('Queued print job '.$jobId.'.');

        return $this->publicStatus($job);
    }

    public function getStatus(string $jobId): array
    {
        $jobId = $this->validateJobId($jobId);
        $pendingPath = $this->pendingPath($jobId);
        if (is_file($pendingPath)) {
            $job = $this->readJson($pendingPath);

            return $this->publicStatus($job);
        }

        $statusPath = $this->statusPath($jobId);
        if (is_file($statusPath)) {
            return $this->readJson($statusPath);
        }

        return [
            'job_id' => $jobId,
            'status' => 'unknown',
            'attempts' => 0,
        ];
    }

    public function processDueJobs(callable $printer, int $maxJobs = 10): int
    {
        $processed = 0;
        $paths = glob($this->pendingDirectory.DIRECTORY_SEPARATOR.'*.json') ?: [];
        sort($paths, SORT_STRING);

        foreach ($paths as $path) {
            if ($processed >= $maxJobs) {
                break;
            }

            $removeAfterUnlock = false;
            $handle = @fopen($path, 'c+');
            if ($handle === false || ! flock($handle, LOCK_EX | LOCK_NB)) {
                if (is_resource($handle)) {
                    fclose($handle);
                }
                continue;
            }

            try {
                rewind($handle);
                $json = stream_get_contents($handle);
                $job = json_decode((string) $json, true, 512, JSON_THROW_ON_ERROR);
                $now = time();

                if (in_array($job['status'] ?? null, ['printed', 'expired'], true)) {
                    // A previous Windows delete may have failed after the terminal
                    // status was persisted. Remove it without printing it again.
                    $removeAfterUnlock = true;
                } elseif (($job['created_at'] ?? $now) < ($now - 86400)) {
                    $job['status'] = 'expired';
                    $job['updated_at'] = $now;
                    $job['last_error'] = 'Receipt expired after 24 hours without printing.';
                    $this->rewriteLockedFile($handle, $job);
                    $this->complete($job, 'expired', 'Receipt expired after 24 hours without printing.');
                    $removeAfterUnlock = true;
                    $this->log('Expired print job '.$job['job_id'].'.');
                    $processed++;
                } elseif (($job['next_attempt_at'] ?? 0) <= $now) {
                    $job['status'] = 'printing';
                    $job['attempts'] = (int) ($job['attempts'] ?? 0) + 1;
                    $job['updated_at'] = $now;
                    $this->rewriteLockedFile($handle, $job);

                    try {
                        $payload = json_decode(
                            json_encode($job['payload'], JSON_THROW_ON_ERROR),
                            false,
                            512,
                            JSON_THROW_ON_ERROR
                        );
                        $printer($payload);
                        $job['status'] = 'printed';
                        $job['updated_at'] = time();
                        $job['last_error'] = null;
                        $this->rewriteLockedFile($handle, $job);
                        $this->complete($job, 'printed');
                        $removeAfterUnlock = true;
                        $this->log('Printed job '.$job['job_id'].' on attempt '.$job['attempts'].'.');
                    } catch (\Throwable $exception) {
                        $delay = $this->retryDelay($job['attempts']);
                        $job['status'] = 'retrying';
                        $job['updated_at'] = time();
                        $job['next_attempt_at'] = time() + $delay;
                        $job['last_error'] = $this->redactError($exception->getMessage());
                        $this->rewriteLockedFile($handle, $job);
                        $this->log(
                            'Print job '.$job['job_id'].' failed on attempt '.$job['attempts'].
                            '; retrying in '.$delay.' seconds: '.$job['last_error']
                        );
                    }

                    $processed++;
                }
            } catch (\Throwable $exception) {
                $this->log('Unable to process queue file '.$path.': '.$this->redactError($exception->getMessage()));
            } finally {
                flock($handle, LOCK_UN);
                fclose($handle);
            }

            if ($removeAfterUnlock && is_file($path) && ! @unlink($path)) {
                $this->log('Unable to remove completed queue file '.$path.'; it will not be printed again.');
            }
        }

        return $processed;
    }

    public function cleanupStatuses(int $retentionSeconds = 172800): void
    {
        $cutoff = time() - $retentionSeconds;
        foreach (glob($this->statusDirectory.DIRECTORY_SEPARATOR.'*.json') ?: [] as $path) {
            if (filemtime($path) !== false && filemtime($path) < $cutoff) {
                @unlink($path);
            }
        }
    }

    public function workerLockPath(): string
    {
        return $this->queueRoot.DIRECTORY_SEPARATOR.'worker.lock';
    }

    private function complete(array $job, string $status, ?string $error = null): void
    {
        $result = [
            'job_id' => $job['job_id'],
            'status' => $status,
            'attempts' => (int) ($job['attempts'] ?? 0),
            'created_at' => (int) ($job['created_at'] ?? time()),
            'updated_at' => time(),
            'last_error' => $error,
        ];
        $this->writeJson($this->statusPath($job['job_id']), $result);
    }

    private function publicStatus(array $job): array
    {
        return [
            'job_id' => $job['job_id'],
            'status' => $job['status'] ?? 'unknown',
            'attempts' => (int) ($job['attempts'] ?? 0),
            'created_at' => isset($job['created_at']) ? (int) $job['created_at'] : null,
            'updated_at' => isset($job['updated_at']) ? (int) $job['updated_at'] : null,
            'next_attempt_at' => isset($job['next_attempt_at']) ? (int) $job['next_attempt_at'] : null,
            'last_error' => $job['last_error'] ?? null,
        ];
    }

    private function retryDelay(int $attempt): int
    {
        $delays = [5, 15, 30, 60, 120, 300];

        return $delays[min(max($attempt - 1, 0), count($delays) - 1)];
    }

    private function pendingPath(string $jobId): string
    {
        return $this->pendingDirectory.DIRECTORY_SEPARATOR.$jobId.'.json';
    }

    private function statusPath(string $jobId): string
    {
        return $this->statusDirectory.DIRECTORY_SEPARATOR.$jobId.'.json';
    }

    private function validateJobId(string $jobId): string
    {
        if (! preg_match('/^[A-Za-z0-9-]{10,100}$/', $jobId)) {
            throw new \InvalidArgumentException('Invalid print job ID.');
        }

        return $jobId;
    }

    private function readJson(string $path): array
    {
        $json = file_get_contents($path);
        if ($json === false) {
            throw new \RuntimeException('Unable to read print queue file.');
        }

        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    private function writeJson(string $path, array $data): void
    {
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        if (file_put_contents($path, $json, LOCK_EX) === false) {
            throw new \RuntimeException('Unable to write print queue file.');
        }
    }

    private function rewriteLockedFile($handle, array $data): void
    {
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        rewind($handle);
        if (! ftruncate($handle, 0) || fwrite($handle, $json) === false) {
            throw new \RuntimeException('Unable to update print queue file.');
        }
        fflush($handle);
    }

    private function redactError(string $message): string
    {
        return preg_replace('#smb://[^/@:]+:[^/@]+@#i', 'smb://***:***@', $message) ?: $message;
    }

    private function log(string $message): void
    {
        if ($this->logger) {
            ($this->logger)($message);
        }
    }
}
