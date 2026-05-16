@extends('layouts.app')
@section('title', __('lang_v1.vat_purchase_schedule'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">{{ __('lang_v1.vat_purchase_schedule') }}</h1>
</section>

<section class="content">
    <div class="row no-print">
        <div class="col-md-12">
            @include('tax_admin.partials.date_location_filters', ['date_range_id' => 'vat_purchase_date_range', 'location_id' => 'vat_purchase_location_id'])
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="vat_purchase_schedule_table">
                        <thead>
                            <tr>
                                <th>@lang('messages.date')</th>
                                <th>@lang('purchase.ref_no')</th>
                                <th>@lang('purchase.supplier')</th>
                                <th>@lang('contact.tax_no')</th>
                                <th>@lang('sale.location')</th>
                                <th>@lang('lang_v1.taxable_amount')</th>
                                <th>@lang('lang_v1.input_vat')</th>
                                <th>@lang('sale.total')</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="bg-gray font-17 footer-total text-center">
                                <td colspan="5"><strong>@lang('sale.total'):</strong></td>
                                <td class="footer_taxable_amount"></td>
                                <td class="footer_vat_amount"></td>
                                <td class="footer_gross_amount"></td>
                            </tr>
                        </tfoot>
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
    initKenyaTaxTable('#vat_purchase_schedule_table', '{{ action([\App\Http\Controllers\ReportController::class, 'kenyaVatPurchaseSchedule']) }}', [
        { data: 'transaction_date', name: 'transactions.transaction_date' },
        { data: 'ref_no', name: 'transactions.ref_no' },
        { data: 'supplier', name: 'c.name' },
        { data: 'tax_number', name: 'c.tax_number' },
        { data: 'location_name', name: 'bl.name' },
        { data: 'total_before_tax', name: 'transactions.total_before_tax' },
        { data: 'tax_amount', name: 'transactions.tax_amount' },
        { data: 'final_total', name: 'transactions.final_total' },
    ], '#vat_purchase_date_range', '#vat_purchase_location_id');
});
</script>
@include('tax_admin.partials.schedule_js')
@endsection
