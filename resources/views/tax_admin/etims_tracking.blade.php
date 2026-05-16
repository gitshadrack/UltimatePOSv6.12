@extends('layouts.app')
@section('title', __('lang_v1.etims_tracking'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">{{ __('lang_v1.etims_tracking') }}</h1>
</section>

<section class="content">
    <div class="row no-print">
        <div class="col-md-12">
            @include('tax_admin.partials.date_location_filters', ['date_range_id' => 'etims_date_range', 'location_id' => 'etims_location_id', 'status_id' => 'etims_status_filter'])
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="etims_tracking_table">
                        <thead>
                            <tr>
                                <th>@lang('messages.date')</th>
                                <th>@lang('sale.invoice_no')</th>
                                <th>@lang('contact.customer')</th>
                                <th>@lang('lang_v1.buyer_pin')</th>
                                <th>@lang('sale.location')</th>
                                <th>@lang('sale.total')</th>
                                <th>@lang('lang_v1.etims_status')</th>
                                <th>@lang('lang_v1.etims_invoice_no')</th>
                                <th>@lang('lang_v1.etims_control_code')</th>
                                <th>@lang('messages.action')</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script>
$(document).ready(function () {
    var etims_table = initKenyaTaxTable('#etims_tracking_table', '{{ action([\App\Http\Controllers\ReportController::class, 'kenyaEtimsTracking']) }}', [
        { data: 'transaction_date', name: 'transactions.transaction_date' },
        { data: 'invoice_no', name: 'transactions.invoice_no' },
        { data: 'customer', name: 'c.name' },
        { data: 'buyer_pin_display', name: 'buyer_pin_display', orderable: false },
        { data: 'location_name', name: 'bl.name' },
        { data: 'final_total', name: 'transactions.final_total' },
        { data: 'etims_status', name: 'transactions.etims_status' },
        { data: 'etims_invoice_no', name: 'transactions.etims_invoice_no' },
        { data: 'etims_control_code', name: 'transactions.etims_control_code' },
        { data: 'action', name: 'action', orderable: false, searchable: false },
    ], '#etims_date_range', '#etims_location_id', '#etims_status_filter');

    $(document).on('click', '.update-etims-tracking', function () {
        var button = $(this);
        var row = button.closest('.input-group');

        $.ajax({
            method: 'POST',
            url: button.data('url'),
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                buyer_pin: row.find('.etims-buyer-pin').val(),
                etims_invoice_no: row.find('.etims-invoice-no').val(),
                etims_control_code: row.find('.etims-control-code').val(),
                etims_status: row.find('.etims-status').val(),
            },
            success: function (result) {
                if (result.success) {
                    toastr.success(result.msg);
                    etims_table.ajax.reload(null, false);
                } else {
                    toastr.error(result.msg);
                }
            },
        });
    });
});
</script>
@include('tax_admin.partials.schedule_js')
@endsection
