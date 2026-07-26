<?php

require_once __DIR__.'/vendor/autoload.php';

use Twf\Pps\DurablePrintQueue;
use Twf\Pps\Escpos;

$queue = new DurablePrintQueue();

function send_message(Hoa\Event\Bucket $bucket, array $message): void
{
    $bucket->getSource()->send(json_encode($message, JSON_UNESCAPED_SLASHES));
}

try {
    echo '> Starting server...', PHP_EOL;

    $websocket = new Hoa\Websocket\Server(
        new Hoa\Socket\Server('ws://127.0.0.1:6441')
    );

    $websocket->on('open', static function (Hoa\Event\Bucket $bucket): void {
        echo '> Connected', PHP_EOL;
    });

    $websocket->on('message', static function (Hoa\Event\Bucket $bucket) use ($queue): void {
        try {
            $eventData = $bucket->getData();
            $request = json_decode($eventData['message'], false, 512, JSON_THROW_ON_ERROR);
            $type = $request->type ?? '';

            if ($type === 'check-status') {
                send_message($bucket, [
                    'type' => 'server-status',
                    'status' => 'ready',
                ]);

                return;
            }

            if ($type === 'check-print-job') {
                send_message($bucket, array_merge(
                    ['type' => 'print-status'],
                    $queue->getStatus((string) ($request->print_job_id ?? ''))
                ));

                return;
            }

            if (in_array($type, ['print-receipt', 'print-data', 'print-img'], true)) {
                if (empty($request->printer_config)) {
                    throw new RuntimeException('No configured receipt printer was supplied.');
                }

                $jobId = ! empty($request->print_job_id)
                    ? (string) $request->print_job_id
                    : $queue->createJobId();
                $status = $queue->enqueue($jobId, $request);
                echo '> Queued print job ', $jobId, PHP_EOL;
                send_message($bucket, array_merge(['type' => 'print-status'], $status));

                return;
            }

            if ($type === 'open-cashdrawer') {
                if (empty($request->printer_config)) {
                    throw new RuntimeException('No configured receipt printer was supplied for the cash drawer.');
                }

                $escpos = new Escpos();
                $escpos->load($request->printer_config);
                $escpos->open_drawer();
                send_message($bucket, [
                    'type' => 'drawer-status',
                    'status' => 'opened',
                ]);
                echo '> Opened cash drawer', PHP_EOL;

                return;
            }

            throw new RuntimeException('Unknown print request type.');
        } catch (Throwable $exception) {
            echo '> Request error: ', $exception->getMessage(), PHP_EOL;
            send_message($bucket, [
                'type' => 'request-error',
                'status' => 'error',
                'message' => $exception->getMessage(),
                'print_job_id' => isset($request) ? ($request->print_job_id ?? null) : null,
            ]);
        }
    });

    $websocket->on('close', static function (Hoa\Event\Bucket $bucket): void {
        echo '> Disconnected', PHP_EOL;
    });

    echo '> Server started', PHP_EOL;
    $websocket->run();
} catch (Throwable $exception) {
    echo '> Fatal server error: ', $exception->getMessage(), PHP_EOL;
    exit(1);
}

