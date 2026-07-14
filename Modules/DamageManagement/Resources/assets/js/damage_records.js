$(document).ready(function () {
    if ($('#damage_records_table').length) {
        var damageRecordsTable = $('#damage_records_table').DataTable({
            processing: true,
            serverSide: true,
            aaSorting: [[1, 'desc']],
            ajax: {
                url: '/damage-records',
                data: function (d) {
                    d.location_id = $('#damage_filter_location').val();
                    d.brand_id = $('#damage_filter_brand').val();
                    d.category_id = $('#damage_filter_category').val();
                    d.customer_id = $('#damage_filter_customer').val();
                    d.supplier_id = $('#damage_filter_supplier').val();
                    d.dispatch_status = $('#damage_filter_status').val();

                    var productSelect = $('#damage_filter_product');
                    var selectedProduct = productSelect.select2('data');
                    if (selectedProduct && selectedProduct.length) {
                        d.product_id = selectedProduct[0].product_id || null;
                    }

                    var dateRange = $('#damage_filter_date_range').val();
                    if (dateRange) {
                        var drFormatted = dateRange.split(' ~ ');
                        var start = moment(drFormatted[0], moment_date_format);
                        var end = moment(drFormatted[1], moment_date_format);
                        if (start.isValid() && end.isValid()) {
                            d.start_date = start.format('YYYY-MM-DD');
                            d.end_date = end.format('YYYY-MM-DD');
                        }
                    }
                },
            },
            columns: [
                { data: 'action', name: 'action', orderable: false, searchable: false },
                { data: 'reference_no', name: 'reference_no' },
                { data: 'product_display', name: 'P.name' },
                { data: 'sub_sku', name: 'V.sub_sku' },
                { data: 'location_name', name: 'BL.name' },
                { data: 'reported_at', name: 'damage_records.reported_at' },
                { data: 'quantity', name: 'damage_records.quantity', searchable: false },
                { data: 'remaining_qty', name: 'remaining_qty', orderable: false, searchable: false },
                { data: 'purchase_value', name: 'damage_records.purchase_value', searchable: false },
                { data: 'sell_value', name: 'damage_records.sell_value', searchable: false },
                { data: 'expected_compensation', name: 'damage_records.expected_compensation', searchable: false },
                { data: 'given_compensation', name: 'damage_records.given_compensation', searchable: false },
                { data: 'dispatch_status_label', name: 'damage_records.dispatch_status' },
                { data: 'customer_name', name: 'CUST.name' },
                { data: 'supplier_name', name: 'SUP.name' },
            ],
            columnDefs: [
                { targets: [2, 13, 14], orderable: false },
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

                var quantityTotal = sumColumn(6);
                var remainingTotal = sumColumn(7);
                var purchaseTotal = sumColumn(8);
                var sellTotal = sumColumn(9);
                var expectedTotal = sumColumn(10);
                var givenTotal = sumColumn(11);

                $(api.column(6).footer()).addClass('display_currency').attr('data-currency_symbol', false).attr('data-is_quantity', true).text(quantityTotal);
                $(api.column(7).footer()).addClass('display_currency').attr('data-currency_symbol', false).attr('data-is_quantity', true).text(remainingTotal);
                $(api.column(8).footer()).addClass('display_currency').attr('data-currency_symbol', true).text(purchaseTotal);
                $(api.column(9).footer()).addClass('display_currency').attr('data-currency_symbol', true).text(sellTotal);
                $(api.column(10).footer()).addClass('display_currency').attr('data-currency_symbol', true).text(expectedTotal);
                $(api.column(11).footer()).addClass('display_currency').attr('data-currency_symbol', true).text(givenTotal);

                __currency_convert_recursively($('#damage_records_table'));
            },
            fnDrawCallback: function () {
                __currency_convert_recursively($('#damage_records_table'));
            },
        });

        var productFilter = $('#damage_filter_product');
        if (productFilter.length) {
            productFilter.select2({
                ajax: {
                    url: productFilter.data('search-url'),
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { term: params.term };
                    },
                    processResults: function (data) {
                        return data;
                    },
                },
                placeholder: productFilter.data('placeholder') || '',
                allowClear: true,
            });
        }

        $('#damage_filter_location, #damage_filter_brand, #damage_filter_category, #damage_filter_status').change(function () {
            damageRecordsTable.ajax.reload();
        });

        $('#damage_filter_product, #damage_filter_customer, #damage_filter_supplier').on('change', function () {
            damageRecordsTable.ajax.reload();
        });

        initDamageContactFilter($('#damage_filter_customer'), 'customer');
        initDamageContactFilter($('#damage_filter_supplier'), 'supplier');

        $('#damage_filter_date_range').daterangepicker(
            dateRangeSettings,
            function (start, end) {
                $('#damage_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                damageRecordsTable.ajax.reload();
            }
        );

        $('#damage_filter_date_range').on('cancel.daterangepicker', function () {
            $('#damage_filter_date_range').val('');
            damageRecordsTable.ajax.reload();
        });

        $(document).on('click', '.delete-damage-record', function (e) {
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
                            damageRecordsTable.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            });
        });
    }
});

function initDamageContactFilter($element, type) {
    if (!$element.length) {
        return;
    }

    $element.select2({
        ajax: {
            url: '/damage-records/contacts',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    term: params.term,
                    type: type,
                };
            },
            processResults: function (data) {
                return data;
            }
        },
        placeholder: $element.data('placeholder') || '',
        allowClear: true,
        width: '100%',
        minimumInputLength: 1
    });
}
