@extends('layouts.app')
@section('title', __('damagemanagement::damage.record_details'))

@section('content')

<section class="content-header no-print">
    <h1>@lang('damagemanagement::damage.record_details') - {{ $record->reference_no }}</h1>
</section>

<section class="content no-print">
    @component('components.widget', ['class' => 'box-primary'])
        <div class="row invoice-info">
            <div class="col-sm-3 invoice-col">
                <strong>@lang('product.product')</strong>
                <p>{{ $record->product->name ?? '-' }}<br>
                   <small>SKU: {{ $record->variation->sub_sku ?? '-' }}</small>
                </p>
            </div>
            <div class="col-sm-3 invoice-col">
                <strong>@lang('business.location')</strong>
                <p>{{ $record->location->name ?? '-' }}</p>
            </div>
            <div class="col-sm-3 invoice-col">
                <strong>@lang('lang_v1.date')</strong>
                <p>{{ @format_datetime($record->reported_at) }}</p>
            </div>
            <div class="col-sm-3 invoice-col">
                <strong>@lang('damagemanagement::damage.dispatch_status')</strong>
                <p>@lang('damagemanagement::damage.dispatch_status_'.$record->dispatch_status)</p>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-3 invoice-col">
                <strong>@lang('product.brand')</strong>
                <p>{{ optional($record->brand)->name ?? '-' }}</p>
            </div>
            <div class="col-sm-3 invoice-col">
                <strong>@lang('product.category')</strong>
                <p>{{ optional($record->category)->name ?? '-' }}</p>
            </div>
            <div class="col-sm-3 invoice-col">
                <strong>@lang('lang_v1.unit')</strong>
                <p>{{ optional($record->unit)->short_name ?? '-' }}</p>
            </div>
            <div class="col-sm-3 invoice-col">
                <strong>@lang('lang_v1.customer')</strong>
                <p>{{ optional($record->customer)->name ?? optional($record->customer)->supplier_business_name ?? '-' }}</p>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-3 invoice-col">
                <strong>@lang('lang_v1.supplier')</strong>
                <p>{{ optional($record->supplier)->name ?? optional($record->supplier)->supplier_business_name ?? '-' }}</p>
            </div>
            <div class="col-sm-3 invoice-col">
                <strong>@lang('lang_v1.quantity')</strong>
                <p>{{ @format_quantity($record->quantity) }}</p>
            </div>
            <div class="col-sm-3 invoice-col">
                <strong>@lang('damagemanagement::damage.dispatched_qty')</strong>
                <p>{{ @format_quantity($record->dispatched_quantity) }}</p>
            </div>
            <div class="col-sm-3 invoice-col">
                <strong>@lang('damagemanagement::damage.remaining_qty')</strong>
                <p>{{ @format_quantity($record->quantity - $record->dispatched_quantity) }}</p>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-4 invoice-col">
                <strong>@lang('damagemanagement::damage.purchase_value')</strong>
                <p><span class="display_currency" data-currency_symbol="true">{{ $record->purchase_value }}</span></p>
            </div>
            <div class="col-sm-4 invoice-col">
                <strong>@lang('damagemanagement::damage.sell_value')</strong>
                <p><span class="display_currency" data-currency_symbol="true">{{ $record->sell_value }}</span></p>
            </div>
            <div class="col-sm-4 invoice-col">
                <strong>@lang('damagemanagement::damage.expected_compensation')</strong>
                <p class="text-success"><span class="display_currency" data-currency_symbol="true">{{ $record->expected_compensation }}</span></p>
            </div>
        </div>

        @if($record->given_compensation > 0)
        <div class="row">
            <div class="col-sm-4 invoice-col">
                <strong>@lang('damagemanagement::damage.given_compensation')</strong>
                <p class="text-warning"><span class="display_currency" data-currency_symbol="true">{{ $record->given_compensation }}</span></p>
            </div>
        </div>
        @endif

        @if(!empty($record->notes))
        <div class="row">
            <div class="col-sm-12">
                <strong>@lang('lang_v1.notes'):</strong>
                <p>{{ $record->notes }}</p>
            </div>
        </div>
        @endif

        @if($record->dispatchLines->isNotEmpty())
        <div class="row">
            <div class="col-sm-12">
                <h4>@lang('damagemanagement::damage.dispatch_history')</h4>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>@lang('lang_v1.reference_no')</th>
                                <th>@lang('lang_v1.date')</th>
                                <th>@lang('lang_v1.quantity')</th>
                                <th>@lang('damagemanagement::damage.compensation_value')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($record->dispatchLines as $line)
                                <tr>
                                    <td>{{ $line->dispatch->reference_no ?? '-' }}</td>
                                    <td>{{ @format_datetime($line->dispatch->dispatched_at) }}</td>
                                    <td>{{ @format_quantity($line->dispatched_quantity) }}</td>
                                    <td><span class="display_currency" data-currency_symbol="true">{{ $line->compensation_amount }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    @endcomponent

    <div class="row">
        <div class="col-sm-12 text-center">
            @if(auth()->user()->can('damage_record.update'))
                <a href="{{ action([\Modules\DamageManagement\Http\Controllers\DamageRecordController::class, 'edit'], [$record->id]) }}" 
                   class="btn btn-info btn-modal" data-container=".view_modal">
                    <i class="fa fa-edit"></i> @lang('messages.edit')
                </a>
            @endif
            @if(auth()->user()->can('damage_record.view'))
                <a href="{{ action([\Modules\DamageManagement\Http\Controllers\DamageRecordController::class, 'print'], [$record->id]) }}" 
                   class="btn btn-default" target="_blank">
                    <i class="fa fa-print"></i> @lang('messages.print')
                </a>
            @endif
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script>
$(document).ready(function() {
    // View/Edit modal handler
    $(document).on('click', '.btn-modal', function(e) {
        e.preventDefault();
        var container = '.view_modal';
        var url = $(this).attr('href');

        if (url) {
            $(container).html('<div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-body text-center"><i class="fa fa-spinner fa-spin fa-3x"></i><br><br>Loading...</div></div></div>').modal('show');

            $.get(url, function(data) {
                $(container).html(data).modal('show');
            }).fail(function() {
                toastr.error('Error loading content');
                $(container).modal('hide');
            });
        }
    });
});
</script>
@endsection
