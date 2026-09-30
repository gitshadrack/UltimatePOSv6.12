var stocktakeScanner = null;
var stocktakeScannerRunning = false;

$(document).ready(function() {
    $('#transaction_date').datetimepicker({format: moment_date_format + ' ' + moment_time_format, ignoreReadonly: true});
    $('#stocktake_form').validate();

    $('#location_id').on('change', function() {
        var enabled = !!$(this).val();
        $('#stocktake_search_product').prop('disabled', !enabled);
        $('#stocktake_scan_button').prop('disabled', !enabled);
        $('#stocktake_product_table tbody').empty();
        $('#stocktake_row_index').val(0);
    });

    $('#stocktake_search_product').autocomplete({
        minLength: 2,
        source: function(request, response) {
            $.getJSON('/products/list', {location_id: $('#location_id').val(), term: request.term, search_fields: ['name', 'sku'], limit: 20}, response);
        },
        select: function(event, ui) {
            event.preventDefault();
            if (ui.item.enable_stock == 1) {
                addStocktakeProduct(ui.item.variation_id, false);
                $(this).val('');
            } else {
                toastr.warning(LANG.stock_not_enabled || 'Stock is not enabled for this product.');
            }
        }
    }).autocomplete('instance')._renderItem = function(ul, item) {
        if (item.enable_stock != 1) return ul;
        var label = item.name + (item.type === 'variable' ? ' - ' + item.variation : '') + ' (' + item.sub_sku + ')';
        return $('<li>').append($('<div>').text(label)).appendTo(ul);
    };

    $(document).on('change keyup', '.stocktake_physical_quantity', function() { updateStocktakeDifference($(this).closest('tr')); });
    $(document).on('click', '.remove_stocktake_row', function() { $(this).closest('tr').remove(); });

    $('#stocktake_scan_button').on('click', function() {
        if (typeof Html5Qrcode === 'undefined') return toastr.error('The camera scanner could not be loaded.');
        $('#stocktake_scanner_modal').modal('show');
    });
    $('#stocktake_scanner_modal').on('shown.bs.modal', startStocktakeScanner).on('hidden.bs.modal', stopStocktakeScanner);
});

function addStocktakeProduct(variationId, scanned) {
    var row = $('#stocktake_product_table .stocktake_variation_id').filter(function() { return String(this.value) === String(variationId); }).closest('tr');
    if (row.length) {
        if (scanned) {
            var input = row.find('.stocktake_physical_quantity');
            __write_number(input, (parseFloat(__read_number(input)) || 0) + 1);
            updateStocktakeDifference(row);
        }
        input = row.find('.stocktake_physical_quantity');
        input.focus().select();
        return;
    }

    var index = parseInt($('#stocktake_row_index').val(), 10);
    $.post('/stock-adjustments/stocktake/product-row', {row_index: index, variation_id: variationId, location_id: $('#location_id').val()})
        .done(function(html) {
            var newRow = $(html);
            $('#stocktake_product_table tbody').append(newRow);
            $('#stocktake_row_index').val(index + 1);
            if (scanned) __write_number(newRow.find('.stocktake_physical_quantity'), 1);
            updateStocktakeDifference(newRow);
            newRow.find('.stocktake_physical_quantity').focus().select();
        }).fail(function(xhr) {
            var message = 'Unable to add this product to the stocktake.';
            if (xhr.status === 419) {
                message = 'Your session has expired. Refresh the page and try again.';
            } else if (xhr.status === 403) {
                message = 'You do not have permission to create stock adjustments.';
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            toastr.error(message);
        });
}

function updateStocktakeDifference(row) {
    var system = parseFloat(row.find('.stocktake_system_quantity').val()) || 0;
    var physical = parseFloat(__read_number(row.find('.stocktake_physical_quantity'))) || 0;
    var difference = physical - system;
    var display = row.find('.stocktake_difference');
    display.text(__number_f(difference)).removeClass('text-success text-danger');
    if (difference > 0) display.addClass('text-success');
    if (difference < 0) display.addClass('text-danger');
}

function startStocktakeScanner() {
    if (stocktakeScannerRunning) return;
    stocktakeScanner = new Html5Qrcode('stocktake_scanner');
    stocktakeScannerRunning = true;
    $('#stocktake_scanner_status').text('Point the camera at a product barcode.');
    stocktakeScanner.start({facingMode: 'environment'}, {fps: 10, qrbox: {width: 280, height: 140}}, function(code) {
        $.when(stopStocktakeScanner()).always(function() {
            $('#stocktake_scanner_modal').modal('hide');
            $.getJSON('/products/list', {location_id: $('#location_id').val(), term: code, search_fields: ['sku'], limit: 5})
                .done(function(products) {
                    if (products.length === 1) addStocktakeProduct(products[0].variation_id, true);
                    else swal(products.length ? 'More than one product matched this barcode.' : LANG.no_products_found);
                }).fail(function() { toastr.error('Unable to search for the scanned barcode.'); });
        });
    }, function() {}).catch(function(error) {
        stocktakeScannerRunning = false;
        var message = 'Camera access failed. Check browser permissions or use the search field.';
        if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') message = 'Camera scanning requires HTTPS.';
        $('#stocktake_scanner_status').text(message);
    });
}

function stopStocktakeScanner() {
    var promise = $.Deferred().resolve().promise();
    if (stocktakeScanner && stocktakeScannerRunning) {
        var scanner = stocktakeScanner;
        promise = scanner.stop().catch(function() {}).then(function() { scanner.clear(); });
    }
    stocktakeScannerRunning = false;
    stocktakeScanner = null;
    return promise;
}
