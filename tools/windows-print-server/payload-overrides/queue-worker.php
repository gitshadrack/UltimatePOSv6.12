<?php

require_once __DIR__.'/vendor/autoload.php';

use Twf\Pps\DurablePrintQueue;
use Twf\Pps\Escpos;

$logDirectory = dirname(__DIR__).DIRECTORY_SEPARATOR.'logs';
if (! is_dir($logDirectory)) {
    mkdir($logDirectory, 0700, true);
}
$logPath = $logDirectory.DIRECTORY_SEPARATOR.'queue-worker.log';

$logger = static function (string $message) use ($logPath): void {
    if (is_file($logPath) && filesize($logPath) > 5 * 1024 * 1024) {
        @rename($logPath, $logPath.'.previous');
    }
    file_put_contents($logPath, date(DATE_ATOM).' '.$message.PHP_EOL, FILE_APPEND | LOCK_EX);
};

$queue = new DurablePrintQueue(null, $logger);
$lockHandle = fopen($queue->workerLockPath(), 'c+');
if ($lockHandle === false || ! flock($lockHandle, LOCK_EX | LOCK_NB)) {
    $logger('Another queue worker is already running; this worker is exiting.');
    exit(0);
}

$logger('Durable print queue worker started.');

while (true) {
    try {
        $queue->processDueJobs(static function (object $payload): void {
            if (empty($payload->printer_config)) {
                throw new RuntimeException('No printer configuration was supplied.');
            }

            $escpos = new Escpos();
            $escpos->load($payload->printer_config);

            switch ($payload->type ?? 'print-receipt') {
                case 'print-receipt':
                    $escpos->print_invoice($payload->data);
                    break;
                case 'print-data':
                    $data = is_string($payload->data) ? json_decode($payload->data) : $payload->data;
                    $escpos->print_invoice($data);
                    break;
                case 'print-img':
                    $escpos->printImg($payload->data->text);
                    break;
                default:
                    throw new RuntimeException('Unsupported queued print type.');
            }
        });
        $queue->cleanupStatuses();
    } catch (Throwable $exception) {
        $logger('Worker loop error: '.$exception->getMessage());
    }

    sleep(3);
}

