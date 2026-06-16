@extends('layouts.app')

@section('title', __('lang_v1.intasend_collections_report'))

@section('content')

<section class="content-header">
    <h1>@lang('lang_v1.intasend_collections_report')</h1>
</section>

<section class="content">
    @if(!empty($migration_required))
        <div class="alert alert-warning">
            @lang('lang_v1.run_intasend_migration')
        </div>
    @endif

    <div class="box box-solid">
        <div class="box-header">
            <h3 class="box-title">@lang('lang_v1.filters')</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('intasend_collection_location_id', __('purchase.business_location') . ':') !!}
                        {!! Form::select('intasend_collection_location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width: 100%;']) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('intasend_collection_status', __('lang_v1.status') . ':') !!}
                        {!! Form::select('intasend_collection_status', [
                            '' => __('lang_v1.all'),
                            'unassigned' => __('lang_v1.unassigned'),
                            'matched' => __('lang_v1.matched'),
                            'attached' => __('lang_v1.attached'),
                            'ignored' => __('lang_v1.ignored'),
                        ], null, ['class' => 'form-control select2', 'style' => 'width: 100%;']) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('intasend_collection_date_range', __('report.date_range') . ':') !!}
                        {!! Form::text('intasend_collection_date_range', null, ['class' => 'form-control', 'readonly']) !!}
                    </div>
                </div>
                <div class="col-md-3 text-right">
                    <a href="{{ action([\App\Http\Controllers\IntaSendController::class, 'pool']) }}" class="btn btn-default" style="margin-top: 24px;">
                        @lang('lang_v1.intasend_holding_pool')
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="box box-solid">
        <div class="box-body">
            <h4>
                @lang('lang_v1.total_collected'): <span id="intasend_total_collected" class="display_currency" data-currency_symbol="true"></span>
                &nbsp; | &nbsp;
                @lang('lang_v1.intasend_total_charges'): <span id="intasend_total_charges" class="display_currency" data-currency_symbol="true"></span>
                &nbsp; | &nbsp;
                @lang('lang_v1.intasend_total_net'): <span id="intasend_total_net_amount" class="display_currency" data-currency_symbol="true"></span>
            </h4>
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="intasend_collections_table">
                    <thead>
                        <tr>
                            <th>@lang('messages.date')</th>
                            <th>@lang('lang_v1.transaction_code')</th>
                            <th>@lang('purchase.business_location')</th>
                            <th>@lang('contact.customer')</th>
                            <th>@lang('lang_v1.phone_number')</th>
                            <th>@lang('lang_v1.intasend_amount_paid')</th>
                            <th>@lang('lang_v1.intasend_charges')</th>
                            <th>@lang('lang_v1.intasend_net_amount')</th>
                            <th>@lang('lang_v1.status')</th>
                            <th>@lang('lang_v1.match_reason')</th>
                            <th>@lang('lang_v1.auto_attached')</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</section>

@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function () {
        if ($('#intasend_collection_date_range').length == 1) {
            $('#intasend_collection_date_range').daterangepicker(dateRangeSettings, function(start, end) {
                $('#intasend_collection_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                intasend_collections_table.ajax.reload();
            });
            $('#intasend_collection_date_range').on('cancel.daterangepicker', function() {
                $('#intasend_collection_date_range').val('');
                intasend_collections_table.ajax.reload();
            });
        }

        var intasend_collections_table = $('#intasend_collections_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ action([\App\Http\Controllers\IntaSendController::class, 'collections']) }}',
                data: function (d) {
                    d.location_id = $('#intasend_collection_location_id').val();
                    d.reconciliation_status = $('#intasend_collection_status').val();
                    if ($('#intasend_collection_date_range').val()) {
                        var dateRange = $('#intasend_collection_date_range').data('daterangepicker');
                        d.start_date = dateRange.startDate.format('YYYY-MM-DD');
                        d.end_date = dateRange.endDate.format('YYYY-MM-DD');
                    }
                }
            },
            columns: [
                { data: 'created_at', name: 'intasend_payments.created_at' },
                { data: 'transaction_code', name: 'intasend_payments.transaction_code' },
                { data: 'location_name', name: 'bl.name' },
                { data: 'contact_name', name: 'c.name' },
                { data: 'phone_number', name: 'intasend_payments.phone_number' },
                { data: 'amount', name: 'intasend_payments.amount' },
                { data: 'charges', name: 'intasend_payments.charges' },
                { data: 'net_amount', name: 'intasend_payments.net_amount' },
                { data: 'reconciliation_status', name: 'intasend_payments.reconciliation_status' },
                { data: 'match_reason', name: 'intasend_payments.match_reason' },
                { data: 'auto_attached', name: 'intasend_payments.auto_attached' },
            ],
            aaSorting: [[0, 'desc']],
            fnDrawCallback: function (settings) {
                var total = settings.json && settings.json.total_collected ? settings.json.total_collected : 0;
                var charges = settings.json && settings.json.total_charges ? settings.json.total_charges : 0;
                var net_amount = settings.json && settings.json.total_net_amount ? settings.json.total_net_amount : 0;
                $('#intasend_total_collected').text(__currency_trans_from_en(total, true));
                $('#intasend_total_charges').text(__currency_trans_from_en(charges, true));
                $('#intasend_total_net_amount').text(__currency_trans_from_en(net_amount, true));
            }
        });

        $('#intasend_collection_location_id, #intasend_collection_status').change(function () {
            intasend_collections_table.ajax.reload();
        });
    });
</script>
@endsection
