const jobId = process.argv[2];
const mode = process.argv[3] || 'status';

if (!jobId) {
    throw new Error('Usage: node Test-QueueProtocol.js <job-id> [enqueue|status]');
}

const socket = new WebSocket('ws://127.0.0.1:6441');
const timeout = setTimeout(() => {
    console.error('Timed out waiting for print-server response.');
    process.exit(2);
}, 5000);

socket.addEventListener('open', () => {
    if (mode === 'enqueue') {
        socket.send(JSON.stringify({
            type: 'print-receipt',
            print_job_id: jobId,
            printer_config: {
                connection_type: 'windows',
                capability_profile: 'default',
                char_per_line: 42,
                path: 'smb://localhost/OfflineQueueTest'
            },
            data: {
                display_name: 'Queue integration test',
                lines: [],
                total: '0.00'
            }
        }));
    } else {
        socket.send(JSON.stringify({
            type: 'check-print-job',
            print_job_id: jobId
        }));
    }
});

socket.addEventListener('message', event => {
    const message = JSON.parse(event.data);
    if (message.type !== 'print-status') {
        return;
    }

    clearTimeout(timeout);
    console.log(JSON.stringify(message));
    socket.close();
    process.exit(0);
});

socket.addEventListener('error', () => {
    clearTimeout(timeout);
    console.error('Unable to connect to the local POS Print Server.');
    process.exit(1);
});

