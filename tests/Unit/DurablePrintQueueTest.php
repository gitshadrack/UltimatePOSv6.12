<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Twf\Pps\DurablePrintQueue;

class DurablePrintQueueTest extends TestCase
{
    private string $queueDirectory;

    protected function setUp(): void
    {
        parent::setUp();
        require_once dirname(__DIR__, 2).'/tools/windows-print-server/payload-overrides/lib/DurablePrintQueue.php';
        $this->queueDirectory = sys_get_temp_dir().'/ultimatepos-print-queue-'.bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->queueDirectory);
        parent::tearDown();
    }

    public function test_receipt_is_persisted_before_processing_and_deduplicated(): void
    {
        $queue = new DurablePrintQueue($this->queueDirectory);
        $payload = (object) [
            'type' => 'print-receipt',
            'printer_config' => (object) ['path' => 'test-printer'],
            'data' => (object) ['invoice_no' => 'INV-100'],
        ];

        $first = $queue->enqueue('receipt-test-job-100', $payload);
        $second = $queue->enqueue('receipt-test-job-100', $payload);

        $this->assertSame('queued', $first['status']);
        $this->assertSame($first, $second);
        $this->assertFileExists(
            $this->queueDirectory.'/pending/receipt-test-job-100.json'
        );
    }

    public function test_failed_job_remains_queued_for_retry(): void
    {
        $queue = new DurablePrintQueue($this->queueDirectory);
        $queue->enqueue('receipt-test-job-200', (object) [
            'type' => 'print-receipt',
            'printer_config' => (object) ['path' => 'offline-printer'],
            'data' => (object) ['invoice_no' => 'INV-200'],
        ]);

        $queue->processDueJobs(static function (): void {
            throw new \RuntimeException('Printer is offline.');
        });

        $status = $queue->getStatus('receipt-test-job-200');
        $this->assertSame('retrying', $status['status']);
        $this->assertSame(1, $status['attempts']);
        $this->assertSame('Printer is offline.', $status['last_error']);
        $this->assertFileExists(
            $this->queueDirectory.'/pending/receipt-test-job-200.json'
        );
    }

    public function test_successful_job_removes_payload_and_keeps_status_only(): void
    {
        $queue = new DurablePrintQueue($this->queueDirectory);
        $queue->enqueue('receipt-test-job-300', (object) [
            'type' => 'print-receipt',
            'printer_config' => (object) ['path' => 'ready-printer'],
            'data' => (object) ['invoice_no' => 'INV-300'],
        ]);

        $printedInvoice = null;
        $queue->processDueJobs(static function (object $payload) use (&$printedInvoice): void {
            $printedInvoice = $payload->data->invoice_no;
        });

        $status = $queue->getStatus('receipt-test-job-300');
        $this->assertSame('INV-300', $printedInvoice);
        $this->assertSame('printed', $status['status']);
        $this->assertSame(1, $status['attempts']);
        $this->assertFileDoesNotExist(
            $this->queueDirectory.'/pending/receipt-test-job-300.json'
        );

        $statusContents = file_get_contents(
            $this->queueDirectory.'/status/receipt-test-job-300.json'
        );
        $this->assertStringNotContainsString('INV-300', $statusContents);
        $this->assertStringNotContainsString('printer_config', $statusContents);
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }
        rmdir($directory);
    }
}

