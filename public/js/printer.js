var socket = null;
var socket_host = 'ws://127.0.0.1:6441';
var socket_connection_promise = null;
var printer_connection_timeout_ms = 3500;
var tracked_print_jobs = {};
var print_job_poll_timer = null;

function printerStatusText(key, fallback) {
    var status = $('#pos-printer-status');
    return status.length && status.data(key) ? status.data(key) : fallback;
}

function setPrinterStatus(state, message) {
    var status = $('#pos-printer-status');
    if (!status.length) {
        return;
    }

    var styles = {
        connecting: {background: '#fff7d6', color: '#7a5600', icon: 'fa-spinner fa-spin'},
        queued: {background: '#fff7d6', color: '#7a5600', icon: 'fa-clock'},
        printing: {background: '#dbeafe', color: '#1e40af', icon: 'fa-spinner fa-spin'},
        ready: {background: '#dcfce7', color: '#166534', icon: 'fa-check-circle'},
        error: {background: '#fee2e2', color: '#991b1b', icon: 'fa-exclamation-triangle'}
    };
    var selected = styles[state] || styles.error;

    status
        .removeClass('hide')
        .css({display: 'inline-flex', backgroundColor: selected.background, color: selected.color})
        .attr('data-state', state)
        .attr('title', message);
    status.find('.printer-status-icon')
        .removeClass('fa-print fa-spinner fa-spin fa-clock fa-check-circle fa-exclamation-triangle')
        .addClass(selected.icon);
    status.find('.printer-status-text').text(message);
}

function setPrinterStatusVisibility(isVisible) {
    var status = $('#pos-printer-status');
    if (!status.length) {
        return;
    }

    if (isVisible) {
        status.removeClass('hide').css('display', 'inline-flex');
    } else {
        status.addClass('hide').css('display', 'none');
    }
}

function printerUnavailableError() {
    return new Error(
        printerStatusText(
            'unavailable-text',
            'Printer offline'
        )
    );
}

function createPrintJobId() {
    if (window.crypto && typeof window.crypto.randomUUID === 'function') {
        return 'receipt-' + window.crypto.randomUUID();
    }

    return 'receipt-' + Date.now() + '-' + Math.random().toString(16).slice(2);
}

function ensurePrintJobPolling() {
    if (print_job_poll_timer != null) {
        return;
    }

    print_job_poll_timer = setInterval(function() {
        var jobIds = Object.keys(tracked_print_jobs);
        if (!jobIds.length) {
            clearInterval(print_job_poll_timer);
            print_job_poll_timer = null;
            return;
        }

        if (socket == null || socket.readyState !== WebSocket.OPEN) {
            return;
        }

        jobIds.forEach(function(jobId) {
            var tracked = tracked_print_jobs[jobId];
            if (tracked && Date.now() - tracked.started_at < 86400000) {
                socket.send(JSON.stringify({
                    type: 'check-print-job',
                    print_job_id: jobId
                }));
            } else {
                delete tracked_print_jobs[jobId];
            }
        });
    }, 3000);
}

function handlePrintServerMessage(event) {
    var message = null;
    try {
        message = JSON.parse(event.data);
    } catch (error) {
        return;
    }

    if (message.type === 'print-status' && message.job_id) {
        if (message.status === 'queued' || message.status === 'retrying') {
            tracked_print_jobs[message.job_id] = tracked_print_jobs[message.job_id] || {
                started_at: Date.now()
            };
            setPrinterStatus('queued', printerStatusText('queued-text', 'Receipt queued'));
            ensurePrintJobPolling();
        } else if (message.status === 'printing') {
            setPrinterStatus('printing', printerStatusText('printing-text', 'Printing...'));
        } else if (message.status === 'printed') {
            delete tracked_print_jobs[message.job_id];
            setPrinterStatus('ready', printerStatusText('printed-text', 'Receipt printed'));
        } else if (message.status === 'expired') {
            delete tracked_print_jobs[message.job_id];
            setPrinterStatus('error', printerStatusText('expired-text', 'Print expired'));
        }
    } else if (message.type === 'request-error') {
        if (message.print_job_id) {
            delete tracked_print_jobs[message.print_job_id];
        }
        setPrinterStatus('error', printerStatusText('unavailable-text', 'Printer offline'));
        toastr.error(message.message || printerStatusText('unavailable-text', 'Printer offline'));
    }
}

initializeSocket = function() {
    if (socket != null && socket.readyState === WebSocket.OPEN) {
        setPrinterStatus('ready', printerStatusText('ready-text', 'Printer ready'));
        return Promise.resolve(socket);
    }

    if (socket_connection_promise != null) {
        return socket_connection_promise;
    }

    setPrinterStatus('connecting', printerStatusText('connecting-text', 'Connecting to printer...'));

    socket_connection_promise = new Promise(function(resolve, reject) {
        var settled = false;
        var connectionTimer = null;

        function failConnection() {
            if (settled) {
                return;
            }
            settled = true;
            clearTimeout(connectionTimer);
            socket_connection_promise = null;
            setPrinterStatus('error', printerStatusText('unavailable-text', 'Printer offline'));
            reject(printerUnavailableError());
        }

        try {
            socket = new WebSocket(socket_host);
            connectionTimer = setTimeout(function() {
                if (socket != null && socket.readyState !== WebSocket.OPEN) {
                    socket.close();
                }
                failConnection();
            }, printer_connection_timeout_ms);

            socket.onopen = function() {
                if (settled) {
                    return;
                }
                settled = true;
                clearTimeout(connectionTimer);
                socket_connection_promise = null;
                setPrinterStatus('ready', printerStatusText('ready-text', 'Printer ready'));
                ensurePrintJobPolling();
                resolve(socket);
            };
            socket.onmessage = handlePrintServerMessage;
            socket.onerror = failConnection;
            socket.onclose = function() {
                socket = null;
                socket_connection_promise = null;
                if (!settled) {
                    failConnection();
                } else {
                    setPrinterStatus('error', printerStatusText('unavailable-text', 'Printer offline'));
                }
            };
        } catch (error) {
            failConnection();
        }
    });

    return socket_connection_promise;
};

function sendToPosPrintServer(content) {
    return initializeSocket().then(function(activeSocket) {
        if (content.type === 'print-receipt') {
            content.print_job_id = content.print_job_id || createPrintJobId();
            tracked_print_jobs[content.print_job_id] = {started_at: Date.now()};
            setPrinterStatus('connecting', printerStatusText('sending-text', 'Sending receipt...'));
            ensurePrintJobPolling();
        }
        activeSocket.send(JSON.stringify(content));
    });
}

function openCashDrawer(printerConfig) {
    return sendToPosPrintServer({
        type: 'open-cashdrawer',
        printer_config: printerConfig || null
    });
}

$(document).on('click', '#pos-printer-status[data-state="error"]', function() {
    initializeSocket().catch(function(error) {
        toastr.error(error.message);
    });
});

$(document).on('click', '.open-cash-drawer', function() {
    var button = $(this);
    var printerConfig = {};
    try {
        printerConfig = JSON.parse(button.attr('data-printer-config') || '{}');
    } catch (e) {
        toastr.error('The configured receipt printer details are invalid.');
        return;
    }
    button.prop('disabled', true);

    openCashDrawer(printerConfig)
        .then(function() {
            toastr.success('Cash drawer command sent.');
        })
        .catch(function(error) {
            toastr.error(error.message);
        })
        .then(function() {
            button.prop('disabled', false);
        });
});
