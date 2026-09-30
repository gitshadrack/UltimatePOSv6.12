var stockAdjustmentScanner = null;
var stockAdjustmentScanInProgress = false;

$(document).ready(function() {

    //Add products
    if ($('#search_product_for_srock_adjustment').length > 0) {
        //Add Product
        $('#search_product_for_srock_adjustment')
            .autocomplete({
                source: function(request, response) {
                    $.getJSON(
                        '/products/list',
                        {
                            location_id: $('#location_id').val(),
                            term: request.term,
                            search_fields: ['name', 'sku'],
                            stock_alert: $('#stock_alert_only').is(':checked') ? 1 : 0,
                            limit: 20,
                        },
                        response
                    );
                },
                minLength: 2,
                response: function(event, ui) {
                    if (ui.content.length == 1) {
                        ui.item = ui.content[0];
                        if (ui.item.qty_available > 0 && ui.item.enable_stock == 1) {
                            $(this)
                                .data('ui-autocomplete')
                                ._trigger('select', 'autocompleteselect', ui);
                            $(this).autocomplete('close');
                        }
                    } else if (ui.content.length == 0) {
                        swal(LANG.no_products_found);
                    }
                },
                focus: function(event, ui) {
                    if (ui.item.qty_available <= 0) {
                        return false;
                    }
                },
                select: function(event, ui) {
                    if (ui.item.qty_available > 0) {
                        $(this).val(null);
                        stock_adjustment_product_row(ui.item.variation_id);
                    } else {
                        alert(LANG.out_of_stock);
                    }
                },
            })
            .autocomplete('instance')._renderItem = function(ul, item) {
            if (item.qty_available <= 0) {
                var string = '<li class="ui-state-disabled">' + item.name;
                if (item.type == 'variable') {
                    string += '-' + item.variation;
                }
                string += ' (' + item.sub_sku + ') (Out of stock) </li>';
                return $(string).appendTo(ul);
            } else if (item.enable_stock != 1) {
                return ul;
            } else {
                var string = '<div>' + item.name;
                if (item.type == 'variable') {
                    string += '-' + item.variation;
                }
                string += ' (' + item.sub_sku + ') </div>';
                return $('<li>')
                    .append(string)
                    .appendTo(ul);
            }
        };
    }

    $('select#location_id').change(function() {
        if ($(this).val()) {
            $('#search_product_for_srock_adjustment').removeAttr('disabled');
        } else {
            $('#search_product_for_srock_adjustment').attr('disabled', 'disabled');
        }
        $('#stock_adjustment_scan_button').prop('disabled', !$(this).val());
        $('table#stock_adjustment_product_table tbody').html('');
        $('#product_row_index').val(0);
        update_table_total();
    });

    $(document).on('change', 'input.product_quantity', function() {
        update_table_row($(this).closest('tr'));
    });
    $(document).on('change', 'input.product_unit_price', function() {
        update_table_row($(this).closest('tr'));
    });

    $(document).on('click', '.remove_product_row', function() {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                $(this)
                    .closest('tr')
                    .remove();
                update_table_total();
            }
        });
    });

    //Date picker
    $('#transaction_date').datetimepicker({
        format: moment_date_format + ' ' + moment_time_format,
        ignoreReadonly: true,
    });

    $('form#stock_adjustment_form').validate();

    $('#stock_alert_only').on('change', function() {
        $('#search_product_for_srock_adjustment').val('').focus();
    });

    $('#stock_adjustment_scan_button').on('click', function() {
        if (!$('#location_id').val()) {
            toastr.warning('Select a location before scanning.');
            return;
        }
        if (typeof Html5Qrcode === 'undefined') {
            toastr.error('The camera scanner could not be loaded. Use the barcode search field instead.');
            return;
        }
        $('#stock_adjustment_scanner_modal').modal('show');
    });

    $('#stock_adjustment_scanner_modal').on('shown.bs.modal', function() {
        start_stock_adjustment_scanner();
    }).on('hidden.bs.modal', function() {
        stop_stock_adjustment_scanner();
    });

    stock_adjustment_table = $('#stock_adjustment_table').DataTable({
        processing: true,
        serverSide: true,
        fixedHeader:false,
        ajax: '/stock-adjustments',
        columnDefs: [
            {
                targets: 0,
                orderable: false,
                searchable: false,
            },
        ],
        aaSorting: [[1, 'desc']],
        columns: [
            { data: 'action', name: 'action' },
            { data: 'transaction_date', name: 'transaction_date' },
            { data: 'ref_no', name: 'ref_no' },
            { data: 'location_name', name: 'BL.name' },
            { data: 'adjustment_type', name: 'adjustment_type' },
            { data: 'final_total', name: 'final_total' },
            { data: 'total_amount_recovered', name: 'total_amount_recovered' },
            { data: 'additional_notes', name: 'additional_notes' },
            { data: 'added_by', name: 'u.first_name' },
        ],
        fnDrawCallback: function(oSettings) {
            __currency_convert_recursively($('#stock_adjustment_table'));
        },
    });
    var detailRows = [];

    $(document).on('click', 'button.delete_stock_adjustment', function() {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                var href = $(this).data('href');
                $.ajax({
                    method: 'DELETE',
                    url: href,
                    dataType: 'json',
                    success: function(result) {
                        if (result.success) {
                            toastr.success(result.msg);
                            stock_adjustment_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });
    });
});

function start_stock_adjustment_scanner() {
    if (stockAdjustmentScanInProgress) {
        return;
    }

    stockAdjustmentScanner = new Html5Qrcode('stock_adjustment_scanner');
    stockAdjustmentScanInProgress = true;
    $('#stock_adjustment_scanner_status').text('Point the camera at a product barcode.');
    stockAdjustmentScanner.start(
        { facingMode: 'environment' },
        { fps: 10, qrbox: { width: 280, height: 140 } },
        function(decodedText) {
            $.when(stop_stock_adjustment_scanner()).always(function() {
                $('#stock_adjustment_scanner_modal').modal('hide');
                lookup_stock_adjustment_barcode(decodedText);
            });
        },
        function() {}
    ).catch(function(error) {
        stockAdjustmentScanInProgress = false;
        var errorMessage = 'Camera access failed. Check browser permissions or use the search field.';
        if (window.location.protocol !== 'https:' && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
            errorMessage = 'Camera scanning requires HTTPS on hosted sites. Open this page over HTTPS, then allow camera access.';
        } else if (error && error.name === 'NotAllowedError') {
            errorMessage = 'Camera permission was denied. Allow camera access for this site in the browser address-bar settings, then try again.';
        } else if (error && error.name === 'NotFoundError') {
            errorMessage = 'No camera was found on this device.';
        }
        $('#stock_adjustment_scanner_status').text(errorMessage);
    });
}

function stop_stock_adjustment_scanner() {
    var stopPromise = $.Deferred().resolve().promise();
    if (stockAdjustmentScanner && stockAdjustmentScanInProgress) {
        var scanner = stockAdjustmentScanner;
        stopPromise = scanner.stop().catch(function() {}).then(function() {
            scanner.clear();
        });
    }
    stockAdjustmentScanInProgress = false;
    stockAdjustmentScanner = null;
    return stopPromise;
}

function lookup_stock_adjustment_barcode(barcode) {
    $.getJSON('/products/list', {
        location_id: $('#location_id').val(),
        term: barcode,
        search_fields: ['sku'],
        stock_alert: $('#stock_alert_only').is(':checked') ? 1 : 0,
        limit: 5,
    }).done(function(products) {
        if (products.length !== 1) {
            swal(products.length ? 'More than one product matched this barcode.' : LANG.no_products_found);
            return;
        }
        add_stock_adjustment_product(products[0].variation_id);
    }).fail(function() {
        toastr.error('Unable to search for the scanned barcode.');
    });
}

function add_stock_adjustment_product(variation_id) {
    var existing_row = $('#stock_adjustment_product_table tbody input[name$="[variation_id]"]').filter(function() {
        return String($(this).val()) === String(variation_id);
    }).closest('tr');

    if (existing_row.length) {
        var quantity_input = existing_row.find('input.product_quantity');
        var quantity = parseFloat(__read_number(quantity_input)) || 0;
        __write_number(quantity_input, quantity + 1);
        update_table_row(existing_row);
        return;
    }

    stock_adjustment_product_row(variation_id);
}

function stock_adjustment_product_row(variation_id) {
    var row_index = parseInt($('#product_row_index').val());
    var location_id = $('select#location_id').val();
    $.ajax({
        method: 'POST',
        url: '/stock-adjustments/get_product_row',
        data: { row_index: row_index, variation_id: variation_id, location_id: location_id },
        dataType: 'html',
        success: function(result) {
            $('table#stock_adjustment_product_table tbody').append(result);
            update_table_total();
            $('#product_row_index').val(row_index + 1);
        },
    });
}

function update_table_total() {
    var table_total = 0;
    $('table#stock_adjustment_product_table tbody tr').each(function() {
        var this_total = parseFloat(__read_number($(this).find('input.product_line_total')));
        if (this_total) {
            table_total += this_total;
        }
    });
    $('input#total_amount').val(table_total);
    $('span#total_adjustment').text(__number_f(table_total));
}

function update_table_row(tr) {
    var quantity = parseFloat(__read_number(tr.find('input.product_quantity')));
    var unit_price = parseFloat(__read_number(tr.find('input.product_unit_price')));
    var row_total = 0;
    if (quantity && unit_price) {
        row_total = quantity * unit_price;
    }
    tr.find('input.product_line_total').val(__number_f(row_total));
    update_table_total();
}

$(document).on('shown.bs.modal', '.view_modal', function() {
    __currency_convert_recursively($('.view_modal'));
});
