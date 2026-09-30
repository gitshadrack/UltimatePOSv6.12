$(document).ready(function() {
    var selectedItems = {};
    var table = $('#stock_alert_lpo_table').DataTable({
        processing: true,
        serverSide: true,
        ordering: false,
        searching: true,
        ajax: {
            url: '/stock-adjustments/stock-alert-lpo/items',
            data: function(data) { data.location_id = $('#lpo_alert_location').val(); }
        },
        columns: [
            {data: 'select_item', searchable: false},
            {data: 'product', name: 'p.name'},
            {data: 'location', name: 'l.name'},
            {data: 'stock', name: 'variation_location_details.qty_available', searchable: false},
            {data: 'alert_quantity', name: 'p.alert_quantity', searchable: false},
            {data: 'order_quantity', searchable: false}
        ],
        drawCallback: function() {
            $('#stock_alert_lpo_table .lpo-alert-select').each(function() {
                var id = String(this.value);
                if (selectedItems[id]) {
                    this.checked = true;
                    $(this).closest('tr').find('.lpo-order-quantity').val(selectedItems[id]);
                }
            });
            __currency_convert_recursively($('#stock_alert_lpo_table'));
        }
    });

    $('#lpo_alert_location').on('change', function() {
        selectedItems = {};
        $('#lpo_select_all').prop('checked', false);
        table.ajax.reload();
    });

    $(document).on('change', '.lpo-alert-select', function() {
        var id = String(this.value);
        if (this.checked) selectedItems[id] = $(this).closest('tr').find('.lpo-order-quantity').val();
        else delete selectedItems[id];
    });
    $(document).on('change', '.lpo-order-quantity', function() {
        var checkbox = $(this).closest('tr').find('.lpo-alert-select');
        if (checkbox.is(':checked')) selectedItems[String(checkbox.val())] = $(this).val();
    });
    $('#lpo_select_all').on('change', function() {
        var checked = this.checked;
        $('#stock_alert_lpo_table .lpo-alert-select').each(function() {
            this.checked = checked;
            $(this).trigger('change');
        });
    });

    $('#stock_alert_lpo_form').on('submit', function(event) {
        $('#lpo_selected_items').empty();
        var index = 0;
        $.each(selectedItems, function(variationId, quantity) {
            var numericQuantity = parseFloat(__read_number($('<input>').val(quantity)));
            if (numericQuantity > 0) {
                $('#lpo_selected_items').append(
                    $('<input>', {type: 'hidden', name: 'items[' + index + '][variation_id]', value: variationId}),
                    $('<input>', {type: 'hidden', name: 'items[' + index + '][quantity]', value: numericQuantity})
                );
                index++;
            }
        });
        if (!index) {
            event.preventDefault();
            toastr.warning('Select at least one stock-alert item and enter a quantity greater than zero.');
        }
    });
});
