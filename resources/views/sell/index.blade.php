@extends('layouts.app')
@section('title', __('lang_v1.all_sales'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header no-print">
        <h1  class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('sale.sells') <span id="sell_list_selected_range" class="tw-text-gray-600 tw-font-normal tw-text-base">{{ @format_date(\Carbon\Carbon::now()->subDays(29)) }} ~ {{ @format_date(\Carbon\Carbon::now()) }}</span>
        </h1>
    </section>

    <!-- Main content -->
    <section class="content no-print">
        @component('components.filters', ['title' => __('report.filters')])
            @include('sell.partials.sell_list_filters')
            @if ($payment_types)
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('payment_method', __('lang_v1.payment_method') . ':') !!}
                        {!! Form::select('payment_method', $payment_types, null, [
                            'class' => 'form-control select2',
                            'style' => 'width:100%',
                            'placeholder' => __('lang_v1.all'),
                        ]) !!}
                    </div>
                </div>
            @endif

            @if (!empty($sources))
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('sell_list_filter_source', __('lang_v1.sources') . ':') !!}

                        {!! Form::select('sell_list_filter_source', $sources, null, [
                            'class' => 'form-control select2',
                            'style' => 'width:100%',
                            'placeholder' => __('lang_v1.all'),
                        ]) !!}
                    </div>
                </div>
            @endif
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('sell_product_sales_category_id', __('product.category') . ':') !!}
                    {!! Form::select('sell_product_sales_category_id', $categories, null, [
                        'class' => 'form-control select2',
                        'style' => 'width:100%',
                        'placeholder' => __('lang_v1.all'),
                    ]) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('sell_product_sales_brand_id', __('product.brand') . ':') !!}
                    {!! Form::select('sell_product_sales_brand_id', $brands, null, [
                        'class' => 'form-control select2',
                        'style' => 'width:100%',
                        'placeholder' => __('lang_v1.all'),
                    ]) !!}
                </div>
            </div>
        @endcomponent
        @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.all_sales')])
            @can('direct_sell.access')
                @slot('tool')
                    <div class="box-tools">
                        <a class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full pull-right"
                            href="{{ action([\App\Http\Controllers\SellController::class, 'create']) }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-plus">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M12 5l0 14" />
                                <path d="M5 12l14 0" />
                            </svg> @lang('messages.add')
                        </a>
                    </div>
                @endslot
            @endcan
            @if (auth()->user()->can('sell.view') ||
                    auth()->user()->can('direct_sell.view') ||
                    auth()->user()->can('view_own_sell_only') ||
                    auth()->user()->can('view_commission_agent_sell'))
                @php
                    $custom_labels = json_decode(session('business.custom_labels'), true);
                @endphp
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs">
                        <li class="active">
                            <a href="#sell_list_tab" data-toggle="tab" aria-expanded="true">
                                <i class="fa fa-list"></i> @lang('lang_v1.all_sales')
                            </a>
                        </li>
                        <li>
                            <a href="#sell_product_sales_tab" data-toggle="tab" aria-expanded="false">
                                <i class="fa fa-cubes"></i> Product Sales
                            </a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active" id="sell_list_tab">
                            <table class="table table-bordered table-striped ajax_view" id="sell_table">
                                <thead>
                                    <tr>
                                        <th>@lang('messages.action')</th>
                                        <th>@lang('messages.date')</th>
                                        <th>@lang('sale.invoice_no')</th>
                                        <th>@lang('sale.customer_name')</th>
                                        <th>@lang('lang_v1.contact_no')</th>
                                        <th>@lang('sale.location')</th>
                                        <th>@lang('sale.payment_status')</th>
                                        <th>@lang('lang_v1.payment_method')</th>
                                        <th>@lang('sale.total_amount')</th>
                                        <th>@lang('sale.total_paid')</th>
                                        <th>@lang('lang_v1.sell_due')</th>
                                        <th>@lang('lang_v1.sell_return_due')</th>
                                        <th>@lang('lang_v1.shipping_status')</th>
                                        <th>@lang('lang_v1.total_items')</th>
                                        <th>@lang('lang_v1.types_of_service')</th>
                                        <th>{{ $custom_labels['types_of_service']['custom_field_1'] ?? __('lang_v1.service_custom_field_1') }}
                                        </th>
                                        <th>{{ $custom_labels['sell']['custom_field_1'] ?? '' }}</th>
                                        <th>{{ $custom_labels['sell']['custom_field_2'] ?? '' }}</th>
                                        <th>{{ $custom_labels['sell']['custom_field_3'] ?? '' }}</th>
                                        <th>{{ $custom_labels['sell']['custom_field_4'] ?? '' }}</th>
                                        <th>@lang('lang_v1.added_by')</th>
                                        <th>@lang('sale.sell_note')</th>
                                        <th>@lang('sale.staff_note')</th>
                                        <th>@lang('sale.shipping_details')</th>
                                        <th>@lang('restaurant.table')</th>
                                        <th>@lang('restaurant.service_staff')</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                                <tfoot>
                                    <tr class="bg-gray font-17 footer-total text-center">
                                        <td colspan="6"><strong>@lang('sale.total'):</strong></td>
                                        <td class="footer_payment_status_count"></td>
                                        <td class="payment_method_count"></td>
                                        <td class="footer_sale_total"></td>
                                        <td class="footer_total_paid"></td>
                                        <td class="footer_total_remaining"></td>
                                        <td class="footer_total_sell_return_due"></td>
                                        <td colspan="2"></td>
                                        <td class="service_type_count"></td>
                                        <td colspan="7"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="tab-pane" id="sell_product_sales_tab">
                            <table class="table table-bordered table-striped ajax_view" id="sell_product_sales_table" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>@lang('sale.product')</th>
                                        <th>@lang('product.sku')</th>
                                        <th>@lang('product.category')</th>
                                        <th>@lang('product.brand')</th>
                                        <th>@lang('sale.location')</th>
                                        <th>@lang('report.user')</th>
                                        <th>@lang('sale.invoice_no')</th>
                                        <th>@lang('messages.date')</th>
                                        <th>@lang('sale.qty')</th>
                                        <th>@lang('sale.unit_price')</th>
                                        <th>@lang('sale.total')</th>
                                        <th>@lang('lang_v1.payment_method')</th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr class="bg-gray font-17 footer-total text-center">
                                        <td colspan="8"><strong>@lang('sale.total'):</strong></td>
                                        <td id="footer_product_sales_qty"></td>
                                        <td></td>
                                        <td><span class="display_currency" id="footer_product_sales_total" data-currency_symbol="true"></span></td>
                                        <td class="product_sales_payment_method_count"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        @endcomponent
    </section>
    <!-- /.content -->
    <div class="modal fade payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

    <!-- This will be printed -->
    <section class="invoice print_section" id="receipt_section">
        </section> 

@stop

@section('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            //Date range as a button
            var startLast30 = moment().subtract(29, 'days');
            var endLast = moment();
            var sell_product_sales_table = null;

            function reloadSellTables() {
                if ($.fn.DataTable.isDataTable('#sell_table')) {
                    sell_table.ajax.reload();
                }

                if (sell_product_sales_table) {
                    sell_product_sales_table.ajax.reload();
                }
            }
            
            // Function to update heading with date range
            function updateDateRangeHeading(start, end) {
                if (start && end) {
                    var formattedStart = start.format(moment_date_format);
                    var formattedEnd = end.format(moment_date_format);
                    $('#sell_list_selected_range').text(formattedStart + ' ~ ' + formattedEnd);
                } else {
                    // Reset to default (last 30 days)
                    var defaultStart = moment().subtract(29, 'days').format(moment_date_format);
                    var defaultEnd = moment().format(moment_date_format);
                    $('#sell_list_selected_range').text(defaultStart + ' ~ ' + defaultEnd);
                }
            }
            
            $('#sell_list_filter_date_range').daterangepicker(
                $.extend(true, {}, dateRangeSettings, { startDate: startLast30, endDate: endLast }),
                function(start, end) {
                    updateDateRangeHeading(start, end);
                    reloadSellTables();
                }
            );
            $('#sell_list_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
                $('#sell_list_filter_date_range').val('');
                updateDateRangeHeading(null, null);
                reloadSellTables();
            });

            sell_table = $('#sell_table').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader:false,
                aaSorting: [
                    [1, 'desc']
                ],
                "ajax": {
                    "url": "/sells",
                    "data": function(d) {
                        if ($('#sell_list_filter_date_range').val()) {
                            var start = $('#sell_list_filter_date_range').data('daterangepicker')
                                .startDate.format('YYYY-MM-DD');
                            var end = $('#sell_list_filter_date_range').data('daterangepicker').endDate
                                .format('YYYY-MM-DD');
                            d.start_date = start;
                            d.end_date = end;
                        }
                        d.is_direct_sale = 1;

                        d.location_id = $('#sell_list_filter_location_id').val();
                        d.customer_id = $('#sell_list_filter_customer_id').val();
                        d.payment_status = $('#sell_list_filter_payment_status').val();
                        d.created_by = $('#created_by').val();
                        d.sales_cmsn_agnt = $('#sales_cmsn_agnt').val();
                        d.service_staffs = $('#service_staffs').val();

                        if ($('#shipping_status').length) {
                            d.shipping_status = $('#shipping_status').val();
                        }

                        if ($('#sell_list_filter_source').length) {
                            d.source = $('#sell_list_filter_source').val();
                        }

                        if ($('#only_subscriptions').is(':checked')) {
                            d.only_subscriptions = 1;
                        }

                        if ($('#payment_method').length) {
                            d.payment_method = $('#payment_method').val();
                        }

                        d = __datatable_ajax_callback(d);
                    }
                },
                scrollY: "75vh",
                scrollX: true,
                scrollCollapse: true,
                columns: [{
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        "searchable": false
                    },
                    {
                        data: 'transaction_date',
                        name: 'transaction_date'
                    },
                    {
                        data: 'invoice_no',
                        name: 'invoice_no'
                    },
                    {
                        data: 'conatct_name',
                        name: 'conatct_name'
                    },
                    {
                        data: 'mobile',
                        name: 'contacts.mobile'
                    },
                    {
                        data: 'business_location',
                        name: 'bl.name'
                    },
                    {
                        data: 'payment_status',
                        name: 'payment_status'
                    },
                    {
                        data: 'payment_methods',
                        orderable: false,
                        "searchable": false
                    },
                    {
                        data: 'final_total',
                        name: 'final_total'
                    },
                    {
                        data: 'total_paid',
                        name: 'total_paid',
                        "searchable": false
                    },
                    {
                        data: 'total_remaining',
                        name: 'total_remaining'
                    },
                    {
                        data: 'return_due',
                        orderable: false,
                        "searchable": false
                    },
                    {
                        data: 'shipping_status',
                        name: 'shipping_status'
                    },
                    {
                        data: 'total_items',
                        name: 'total_items',
                        "searchable": false
                    },
                    {
                        data: 'types_of_service_name',
                        name: 'tos.name',
                        @if (empty($is_types_service_enabled))
                            visible: false
                        @endif
                    },
                    {
                        data: 'service_custom_field_1',
                        name: 'service_custom_field_1',
                        @if (empty($is_types_service_enabled))
                            visible: false
                        @endif
                    },
                    {
                        data: 'custom_field_1',
                        name: 'transactions.custom_field_1',
                        @if (empty($custom_labels['sell']['custom_field_1']))
                            visible: false
                        @endif
                    },
                    {
                        data: 'custom_field_2',
                        name: 'transactions.custom_field_2',
                        @if (empty($custom_labels['sell']['custom_field_2']))
                            visible: false
                        @endif
                    },
                    {
                        data: 'custom_field_3',
                        name: 'transactions.custom_field_3',
                        @if (empty($custom_labels['sell']['custom_field_3']))
                            visible: false
                        @endif
                    },
                    {
                        data: 'custom_field_4',
                        name: 'transactions.custom_field_4',
                        @if (empty($custom_labels['sell']['custom_field_4']))
                            visible: false
                        @endif
                    },
                    {
                        data: 'added_by',
                        name: 'u.first_name'
                    },
                    {
                        data: 'additional_notes',
                        name: 'additional_notes'
                    },
                    {
                        data: 'staff_note',
                        name: 'staff_note'
                    },
                    {
                        data: 'shipping_details',
                        name: 'shipping_details'
                    },
                    {
                        data: 'table_name',
                        name: 'tables.name',
                        @if (empty($is_tables_enabled))
                            visible: false
                        @endif
                    },
                    {
                        data: 'waiter',
                        name: 'ss.first_name',
                        @if (empty($is_service_staff_enabled))
                            visible: false
                        @endif
                    },
                ],
                "fnDrawCallback": function(oSettings) {
                    __currency_convert_recursively($('#sell_table'));
                },
                "footerCallback": function(row, data, start, end, display) {
                    var footer_sale_total = 0;
                    var footer_total_paid = 0;
                    var footer_total_remaining = 0;
                    var footer_total_sell_return_due = 0;
                    for (var r in data) {
                        footer_sale_total += $(data[r].final_total).data('orig-value') ? parseFloat($(
                            data[r].final_total).data('orig-value')) : 0;
                        footer_total_paid += $(data[r].total_paid).data('orig-value') ? parseFloat($(
                            data[r].total_paid).data('orig-value')) : 0;
                        footer_total_remaining += $(data[r].total_remaining).data('orig-value') ?
                            parseFloat($(data[r].total_remaining).data('orig-value')) : 0;
                        footer_total_sell_return_due += $(data[r].return_due).find('.sell_return_due')
                            .data('orig-value') ? parseFloat($(data[r].return_due).find(
                                '.sell_return_due').data('orig-value')) : 0;
                    }

                    $('.footer_total_sell_return_due').html(__currency_trans_from_en(
                        footer_total_sell_return_due));
                    $('.footer_total_remaining').html(__currency_trans_from_en(footer_total_remaining));
                    $('.footer_total_paid').html(__currency_trans_from_en(footer_total_paid));
                    $('.footer_sale_total').html(__currency_trans_from_en(footer_sale_total));

                    $('.footer_payment_status_count').html(__count_status(data, 'payment_status'));
                    $('.service_type_count').html(__count_status(data, 'types_of_service_name'));
                    $('.payment_method_count').html(__count_status(data, 'payment_methods'));
                },
                createdRow: function(row, data, dataIndex) {
                    $(row).find('td:eq(6)').attr('class', 'clickable_td');
                }
            });

            sell_product_sales_table = $('#sell_product_sales_table').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader: false,
                aaSorting: [
                    [7, 'desc']
                ],
                ajax: {
                    url: "/sells/product-sales",
                    data: function(d) {
                        if ($('#sell_list_filter_date_range').val()) {
                            var start = $('#sell_list_filter_date_range').data('daterangepicker')
                                .startDate.format('YYYY-MM-DD');
                            var end = $('#sell_list_filter_date_range').data('daterangepicker').endDate
                                .format('YYYY-MM-DD');
                            d.start_date = start;
                            d.end_date = end;
                        }

                        d.location_id = $('#sell_list_filter_location_id').val();
                        d.created_by = $('#created_by').val();
                        d.sales_cmsn_agnt = $('#sales_cmsn_agnt').val();
                        d.category_id = $('#sell_product_sales_category_id').val();
                        d.brand_id = $('#sell_product_sales_brand_id').val();

                        d = __datatable_ajax_callback(d);
                    }
                },
                scrollY: "75vh",
                scrollX: true,
                scrollCollapse: true,
                columns: [
                    { data: 'product_name', name: 'p.name' },
                    { data: 'sub_sku', name: 'v.sub_sku' },
                    { data: 'category_name', name: 'cat.name' },
                    { data: 'brand_name', name: 'b.name' },
                    { data: 'business_location', name: 'bl.name' },
                    { data: 'sales_rep', name: 'sales_rep' },
                    { data: 'invoice_no', name: 't.invoice_no' },
                    { data: 'transaction_date', name: 't.transaction_date' },
                    { data: 'sell_qty', name: 'sell_qty', searchable: false },
                    { data: 'unit_sale_price', name: 'transaction_sell_lines.unit_price_inc_tax' },
                    { data: 'subtotal', name: 'subtotal', searchable: false },
                    { data: 'payment_methods', orderable: false, searchable: false }
                ],
                fnDrawCallback: function(oSettings) {
                    $('#footer_product_sales_total').text(
                        sum_table_col($('#sell_product_sales_table'), 'row_subtotal')
                    );
                    $('#footer_product_sales_qty').html(
                        __sum_stock($('#sell_product_sales_table'), 'sell_qty')
                    );
                    var product_sales_rows = oSettings.json ? oSettings.json.data : [];
                    $('.product_sales_payment_method_count').html(
                        __count_status(product_sales_rows, 'payment_methods')
                    );
                    __currency_convert_recursively($('#sell_product_sales_table'));
                }
            });

            $(document).on('change',
                '#sell_list_filter_location_id, #sell_list_filter_customer_id, #sell_list_filter_payment_status, #created_by, #sales_cmsn_agnt, #service_staffs, #shipping_status, #sell_list_filter_source, #payment_method',
                function() {
                    reloadSellTables();
                });

            $(document).on('change', '#sell_product_sales_category_id, #sell_product_sales_brand_id', function() {
                if (sell_product_sales_table) {
                    sell_product_sales_table.ajax.reload();
                }
            });

            $('#only_subscriptions').on('ifChanged', function(event) {
                reloadSellTables();
            });

            $('a[href="#sell_product_sales_tab"]').on('shown.bs.tab', function() {
                if (sell_product_sales_table) {
                    sell_product_sales_table.columns.adjust();
                }
            });
        });
    </script>
    <script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
@endsection
