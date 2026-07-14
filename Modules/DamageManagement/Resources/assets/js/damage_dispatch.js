$(document).ready(function () {
    var dispatchTable = null;
    var availableRecordsTable = null;
    var dispatchRowIndex = 0;

    function reloadAvailableRecords() {
        if (availableRecordsTable) {
            availableRecordsTable.ajax.reload();
        }
    }

    function updateDispatchTotals() {
        var totalQuantity = 0;
        var totalPurchase = 0;
        var totalSell = 0;
        var totalCompensation = 0;

        $('#selected_damage_records_table tbody tr').each(function () {
            var row = $(this);
            if (row.hasClass('empty-row')) {
                return;
            }
            var quantity = __read_number(row.find('.dispatch-quantity'));
            var compensation = __read_number(row.find('.compensation-amount'));
            var unitPurchase = parseFloat(row.data('unit-purchase')) || 0;
            var unitSell = parseFloat(row.data('unit-sell')) || 0;

            totalQuantity += quantity;
            totalCompensation += compensation;
            totalPurchase += quantity * unitPurchase;
            totalSell += quantity * unitSell;
        });

        $('#dispatch_total_quantity').text(__number_f(totalQuantity, false, false, __quantity_precision));
        $('#dispatch_total_purchase').text(totalPurchase);
        $('#dispatch_total_sell').text(totalSell);
        $('#dispatch_total_compensation').text(totalCompensation);
        $('#dispatch_total_compensation_display').text(totalCompensation);

        __currency_convert_recursively($('#selected_damage_records_table').parent());
    }

    function ensureEmptyRow() {
        var tbody = $('#selected_damage_records_table tbody');
        if (tbody.find('tr').not('.empty-row').length === 0) {
            if (tbody.find('.empty-row').length === 0) {
                tbody.append(
                    '<tr class="empty-row"><td colspan="6" class="text-center text-muted">' +
                        LANG.damage_no_records_selected +
                        '</td></tr>'
                );
            }
        } else {
            tbody.find('.empty-row').remove();
        }
    }

    if ($('#damage_records_table').length === 0 && $('#damage_dispatch_table').length) {
        dispatchTable = $('#damage_dispatch_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/damage-dispatches',
                data: function (d) {
                    d.location_id = $('#damage_dispatch_filter_location').val();
                    var dateRange = $('#damage_dispatch_filter_date_range').val();
                    if (dateRange) {
                        var dr = dateRange.split(' ~ ');
                        var start = moment(dr[0], moment_date_format);
                        var end = moment(dr[1], moment_date_format);
                        if (start.isValid() && end.isValid()) {
                            d.start_date = start.format('YYYY-MM-DD');
                            d.end_date = end.format('YYYY-MM-DD');
                        }
                    }
                },
            },
            columns: [
                { data: 'action', name: 'action', orderable: false, searchable: false },
                { data: 'reference_no', name: 'dispatch_damages.reference_no' },
                { data: 'dispatched_at', name: 'dispatch_damages.dispatched_at' },
                { data: 'location_name', name: 'BL.name' },
                { data: 'total_purchase_value', name: 'dispatch_damages.total_purchase_value', searchable: false },
                { data: 'total_sell_value', name: 'dispatch_damages.total_sell_value', searchable: false },
                { data: 'total_compensation_value', name: 'dispatch_damages.total_compensation_value', searchable: false },
                { data: 'created_by_name', name: 'U.first_name' },
                { data: 'notes', name: 'dispatch_damages.notes' },
            ],
            buttons: $.extend(true, [], $.fn.dataTable.defaults.buttons),
            footerCallback: function () {
                var api = this.api();
                var parseNumeric = typeof __number_uf === 'function'
                    ? __number_uf
                    : function (value) {
                        if (typeof value === 'number') {
                            return value;
                        }
                        var normalized = (value || '').toString().replace(/[^0-9\-,.]/g, '');
                        normalized = normalized.replace(/,/g, '.');
                        var parsed = parseFloat(normalized);
                        return isNaN(parsed) ? 0 : parsed;
                    };
                var sumColumn = function (index) {
                    return api.column(index, { page: 'current' }).data().reduce(function (acc, val) {
                        return acc + parseNumeric(val);
                    }, 0);
                };
                var purchaseTotal = sumColumn(4);
                var sellTotal = sumColumn(5);
                var compensationTotal = sumColumn(6);

                $(api.column(4).footer()).addClass('display_currency').attr('data-currency_symbol', true).text(purchaseTotal);
                $(api.column(5).footer()).addClass('display_currency').attr('data-currency_symbol', true).text(sellTotal);
                $(api.column(6).footer()).addClass('display_currency').attr('data-currency_symbol', true).text(compensationTotal);
                __currency_convert_recursively($('#damage_dispatch_table'));
            },
            fnDrawCallback: function () {
                __currency_convert_recursively($('#damage_dispatch_table'));
            },
        });

        $('#damage_dispatch_filter_location').change(function () {
            dispatchTable.ajax.reload();
        });

        $('#damage_dispatch_filter_date_range').daterangepicker(
            dateRangeSettings,
            function (start, end) {
                $('#damage_dispatch_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                dispatchTable.ajax.reload();
            }
        );

        $('#damage_dispatch_filter_date_range').on('cancel.daterangepicker', function () {
            $('#damage_dispatch_filter_date_range').val('');
            dispatchTable.ajax.reload();
        });

        $(document).on('click', '.delete-damage-dispatch', function (e) {
            e.preventDefault();
            var href = $(this).data('href');
            swal({
                title: LANG.sure,
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then(function (willDelete) {
                if (!willDelete) {
                    return;
                }
                $.ajax({
                    method: 'DELETE',
                    url: href,
                    dataType: 'json',
                    success: function (result) {
                        if (result.success) {
                            toastr.success(result.msg);
                            dispatchTable.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            });
        });
    }

    if ($('#damage_dispatch_form').length) {
        availableRecordsTable = $('#available_damage_records_table').DataTable({
            processing: true,
            serverSide: true,
            searching: true,
            ajax: {
                url: '/damage-dispatches/available-records',
                data: function (d) {
                    d.location_id = $('#damage_dispatch_location').val();
                },
            },
            columns: [
                { data: 'action', name: 'action', orderable: false, searchable: false },
                { data: 'reference_no', name: 'damage_records.reference_no' },
                { data: 'product_display', name: 'P.name' },
                { data: 'quantity', name: 'damage_records.quantity', searchable: false },
                { data: 'dispatched_quantity', name: 'damage_records.dispatched_quantity', searchable: false },
                { data: 'remaining_quantity', name: 'remaining_quantity', orderable: false, searchable: false },
                { data: 'expected_compensation', name: 'damage_records.expected_compensation', searchable: false },
                { data: 'reported_at', name: 'damage_records.reported_at' },
            ],
            buttons: $.extend(true, [], $.fn.dataTable.defaults.buttons),
            fnDrawCallback: function () {
                __currency_convert_recursively($('#available_damage_records_table'));
            },
        });

        $('#damage_dispatch_location').change(function () {
            reloadAvailableRecords();
        });

        $('#available_damage_records_table').on('click', '.add-damage-record', function (e) {
            e.preventDefault();
            var rowData = availableRecordsTable.row($(this).closest('tr')).data();
            if (!rowData) {
                return;
            }

            var recordId = rowData.id;
            if ($('#selected_damage_records_table tbody tr[data-record-id="' + recordId + '"]').length > 0) {
                toastr.warning(LANG.damage_record_already_added || 'Already added');
                return;
            }

            var remainingRaw = rowData.remaining_quantity_raw !== undefined ? parseFloat(rowData.remaining_quantity_raw) : (parseFloat(rowData.quantity) - parseFloat(rowData.dispatched_quantity));
            var remaining = __number_uf(rowData.remaining_quantity || remainingRaw);
            if (isNaN(remaining) || remaining <= 0) {
                toastr.error(LANG.damage_no_remaining_quantity || 'No remaining quantity to dispatch');
                return;
            }

            var tbody = $('#selected_damage_records_table tbody');
            var expectedCompRaw = rowData.expected_compensation_raw !== undefined ? rowData.expected_compensation_raw : __number_uf(rowData.expected_compensation);
            var newRow =
                '<tr data-row-index="' + dispatchRowIndex + '" data-record-id="' + recordId + '"' +
                ' data-unit-purchase="' + rowData.unit_purchase_price + '"' +
                ' data-unit-sell="' + rowData.unit_sell_price + '">' +
                '<td>' + rowData.product_display + '</td>' +
                '<td>' + rowData.reference_no + '</td>' +
                '<td class="remaining-qty">' + rowData.remaining_quantity + '</td>' +
                '<td>' +
                '<input type="hidden" name="records[' + dispatchRowIndex + '][id]" value="' + recordId + '">' +
                '<input type="text" class="form-control input_number dispatch-quantity" name="records[' + dispatchRowIndex + '][dispatch_quantity]" value="' + remainingRaw + '" data-max="' + remainingRaw + '">' +
                '</td>' +
                '<td>' +
                '<input type="text" class="form-control input_number compensation-amount" name="records[' + dispatchRowIndex + '][compensation_amount]" value="' + expectedCompRaw + '">' +
                '</td>' +
                '<td><button type="button" class="btn btn-danger btn-xs remove-dispatch-line"><i class="fa fa-times"></i></button></td>' +
                '</tr>';

            tbody.append(newRow);
            dispatchRowIndex++;
            ensureEmptyRow();
            updateDispatchTotals();
        });

        $('#selected_damage_records_table').on('click', '.remove-dispatch-line', function () {
            $(this).closest('tr').remove();
            ensureEmptyRow();
            updateDispatchTotals();
        });

        $('#selected_damage_records_table').on('change', '.dispatch-quantity', function () {
            var input = $(this);
            var maxQty = __number_uf(input.data('max'));
            var value = __read_number(input);
            if (isNaN(value) || value <= 0) {
                value = maxQty;
            }
            if (value > maxQty) {
                toastr.warning(LANG.damage_dispatch_qty_exceeds || 'Quantity exceeds remaining amount');
                value = maxQty;
            }
            __write_number(input, value);
            updateDispatchTotals();
        });

        $('#selected_damage_records_table').on('change', '.compensation-amount', function () {
            updateDispatchTotals();
        });

        $('#damage_dispatch_form').on('submit', function (e) {
            if ($('#selected_damage_records_table tbody tr').not('.empty-row').length === 0) {
                e.preventDefault();
                toastr.error(LANG.damage_select_records_first || 'Please add at least one damage record');
                return false;
            }
            updateDispatchTotals();
            return true;
        });

        ensureEmptyRow();
        updateDispatchTotals();

        $('#damage_dispatched_at').datetimepicker({
            format: moment_date_format + ' ' + moment_time_format,
            ignoreReadonly: true,
        });
    }
});
