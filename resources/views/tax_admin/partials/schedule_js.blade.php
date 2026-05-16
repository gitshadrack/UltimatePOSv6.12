<script>
function initKenyaTaxTable(tableSelector, ajaxUrl, columns, dateRangeSelector, locationSelector, statusSelector) {
    if ($(dateRangeSelector).length) {
        $(dateRangeSelector).daterangepicker({
            ranges: ranges,
            autoUpdateInput: false,
            locale: {
                format: moment_date_format,
                cancelLabel: LANG.clear,
                applyLabel: LANG.apply,
                customRangeLabel: LANG.custom_range,
            },
        });
        $(dateRangeSelector).on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format(moment_date_format) + ' ~ ' + picker.endDate.format(moment_date_format));
            $(tableSelector).DataTable().ajax.reload();
        });
        $(dateRangeSelector).on('cancel.daterangepicker', function() {
            $(this).val('');
            $(tableSelector).DataTable().ajax.reload();
        });
    }

    var table = $(tableSelector).DataTable({
        processing: true,
        serverSide: true,
        scrollY: '75vh',
        scrollX: true,
        scrollCollapse: true,
        fixedHeader: false,
        ajax: {
            url: ajaxUrl,
            data: function (d) {
                var range = $(dateRangeSelector).data('daterangepicker');
                if ($(dateRangeSelector).val() && range) {
                    d.start_date = range.startDate.format('YYYY-MM-DD');
                    d.end_date = range.endDate.format('YYYY-MM-DD');
                }
                d.location_id = $(locationSelector).val();
                if (statusSelector) {
                    d.etims_status = $(statusSelector).val();
                }
            },
        },
        columns: columns,
        footerCallback: function(row, data) {
            var taxable = 0;
            var vat = 0;
            var gross = 0;

            for (var r in data) {
                taxable += $(data[r].total_before_tax).data('orig-value') ? parseFloat($(data[r].total_before_tax).data('orig-value')) : 0;
                vat += $(data[r].tax_amount).data('orig-value') ? parseFloat($(data[r].tax_amount).data('orig-value')) : 0;
                gross += $(data[r].final_total).data('orig-value') ? parseFloat($(data[r].final_total).data('orig-value')) : 0;
            }

            $('.footer_taxable_amount').html(__currency_trans_from_en(taxable));
            $('.footer_vat_amount').html(__currency_trans_from_en(vat));
            $('.footer_gross_amount').html(__currency_trans_from_en(gross));
        },
    });

    $(locationSelector + (statusSelector ? ', ' + statusSelector : '')).change(function () {
        table.ajax.reload();
    });

    return table;
}
</script>
