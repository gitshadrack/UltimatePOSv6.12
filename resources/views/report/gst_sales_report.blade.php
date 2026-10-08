@extends('layouts.app')
@section('title', __('kenya_vat.sales_report'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">{{ __('kenya_vat.sales_report')}}</h1>
</section>

<!-- Main content -->
<section class="content no-print">
    <p class="help-block">{{ __('kenya_vat.report_help') }}</p>
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('gst_report_customer_filter', __('contact.customer') . ':') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-user"></i>
                            </span>
                            {!! Form::select('customer_id', $customers, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required', 'id' => 'gst_report_customer_filter', 'style' => 'width:100%']); !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('gst_sr_date_filter', __('report.date_range') . ':') !!}
                        {!! Form::text('date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'gst_sr_date_filter', 'readonly']); !!}
                    </div>
                </div>
            @endcomponent
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" 
                    id="gst_sales_report" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>@lang('sale.invoice_no')</th>
                                <th>@lang('sale.customer_name')</th>
                                <th>@lang('lang_v1.kra_pin')</th>
                                <th>@lang('lang_v1.invoice_date')</th>
                                <th>@lang('lang_v1.etims_invoice_no')</th>
                                <th>@lang('kenya_vat.product_description')</th>
                                <th>@lang('kenya_vat.vat_rate')</th>
                                <th>@lang('sale.qty')</th>
                                <th>@lang('sale.unit_price')</th>
                                <th>@lang('sale.discount')</th>
                                <th>@lang('kenya_vat.value_excluding_vat')</th>
                                <th>@lang('kenya_vat.output_vat')</th>
                                <th>@lang('sale.total')</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="bg-gray font-17 footer-total text-center">
                                <td colspan="10"><strong>@lang('kenya_vat.page_total'):</strong></td>
                                <td class="total_taxable_value"></td>
                                <td class="vat_amount_total"></td>
                                <td class="line_total"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>
</section>
<!-- /.content -->

@endsection

@section('javascript')
    <script type="text/javascript">
        $(document).ready( function(){
            dateRangeSettings.startDate = moment().startOf('month');
            dateRangeSettings.endDate = moment().endOf('month');
            $('#gst_sr_date_filter').daterangepicker(
                dateRangeSettings, 
                function(start, end) {
                    $('#gst_sr_date_filter').val(
                        start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
                    );
                    gst_sales_report.ajax.reload();
                }
            );
            $('#gst_sr_date_filter').on('cancel.daterangepicker', function(ev, picker) {
                $('#gst_sr_date_filter').val('');
                gst_sales_report.ajax.reload();
            });

            $('#gst_report_customer_filter').change(function() {
                gst_sales_report.ajax.reload();
            });
            gst_sales_report = $('table#gst_sales_report').DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [[3, 'desc']],
                scrollY: "75vh",
                scrollX:        true,
                scrollCollapse: true,
                fixedHeader:false,
                ajax: {
                    url: @json(action([\App\Http\Controllers\ReportController::class, 'gstSalesReport'])),
                    data: function(d) {
                        var start = '';
                        var end = '';

                        if ($('#gst_sr_date_filter').val()) {
                            start = $('input#gst_sr_date_filter')
                                .data('daterangepicker')
                                .startDate.format('YYYY-MM-DD');

                            end = $('input#gst_sr_date_filter')
                                .data('daterangepicker')
                                .endDate.format('YYYY-MM-DD');
                        }
                        d.start_date = start;
                        d.end_date = end;
                        d.customer_id = $('select#gst_report_customer_filter').val();
                    },
                },
                columns: [
                    { data: 'invoice_no', name: 't.invoice_no' },
                    { data: 'customer', name: 'c.name' },
                    { data: 'tax_number', searchable: false, orderable: false },
                    { data: 'transaction_date', name: 't.transaction_date' },
                    { data: 'etims_invoice_no', name: 't.etims_invoice_no' },
                    { data: 'product_name', name: 'p.name' },
                    { data: 'tax_percent', name: 'tr.amount' },
                    { data: 'sell_qty', name: 'transaction_sell_lines.quantity' },
                    { data: 'unit_price', name: 'transaction_sell_lines.unit_price_before_discount' },
                    { data: 'discount_amount', name: 'transaction_sell_lines.line_discount_amount' },
                    { data: 'taxable_value', searchable: false, orderable: false },

                    { data: 'vat_amount', searchable: false, orderable: false },

                    { data: 'line_total', name: 'line_total', searchable: false },
                ],
                "footerCallback": function ( row, data, start, end, display ) {
                    var total_taxable_value = 0;
                    var line_total = 0;

                    var vat_amount_total = 0;

                    for (var r in data){
                        total_taxable_value += $(data[r].taxable_value).data('orig-value') ? 
                        parseFloat($(data[r].taxable_value).data('orig-value')) : 0;

                        line_total += $(data[r].line_total).data('orig-value') ? 
                        parseFloat($(data[r].line_total).data('orig-value')) : 0;

                        vat_amount_total += parseFloat($(data[r].vat_amount).data('orig-value')) || 0;
                    }

                    $('.vat_amount_total').html(__currency_trans_from_en(vat_amount_total, false));

                    $('.total_taxable_value').html(__currency_trans_from_en(total_taxable_value, false));
                    $('.line_total').html(__currency_trans_from_en(line_total, false));
                },
            });
        })
    </script>
@endsection