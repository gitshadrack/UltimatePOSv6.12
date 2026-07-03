var socket = null;
var socket_host = 'ws://127.0.0.1:6441';

initializeSocket = function() {
    try {
        if (socket == null) {
            socket = new WebSocket(socket_host);
            socket.onopen = function() {};
            socket.onmessage = function(msg) {};
            socket.onclose = function() {
                socket = null;
            };
        }
    } catch (e) {
        console.log(e);
    }
};

function openCashDrawer(printerConfig) {
    var message = JSON.stringify({
        type: 'open-cashdrawer',
        printer_config: printerConfig || null
    });

    if (socket != null && socket.readyState === WebSocket.OPEN) {
        socket.send(message);
        return Promise.resolve();
    }

    initializeSocket();

    return new Promise(function(resolve, reject) {
        var attempts = 0;
        var timer = setInterval(function() {
            attempts++;

            if (socket != null && socket.readyState === WebSocket.OPEN) {
                clearInterval(timer);
                socket.send(message);
                resolve();
            } else if (attempts >= 10) {
                clearInterval(timer);
                reject(new Error('POS Print Server is not running on this computer (port 6441). Start it and try again.'));
            }
        }, 200);
    });
}

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
