@extends('layouts.app')

@section('title', __('damagemanagement::damage.dispatch_details'))

@section('content')
<section class="content-header">
    <h1>@lang('damagemanagement::damage.dispatch_details')</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        <div class="row">
            <div class="col-md-6">
                <strong>@lang('lang_v1.reference_no'):</strong> {{ $dispatch->reference_no }}
            </div>
            <div class="col-md-6">
                <strong>@lang('lang_v1.date'):</strong> {{ @format_datetime($dispatch->dispatched_at) }}
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <strong>@lang('business.business_location'):</strong> {{ optional($dispatch->location)->name }}
            </div>
        </div>

        <div class="row mt-15">
            <div class="col-md-12">
                <h4>@lang('damagemanagement::damage.dispatch_lines')</h4>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>@lang('product.product')</th>
                            <th>@lang('lang_v1.quantity')</th>
                            <th>@lang('damagemanagement::damage.purchase_value')</th>
                            <th>@lang('damagemanagement::damage.compensation_value')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dispatch->lines as $line)
                            <tr>
                                <td>{{ optional($line->damageRecord->product)->name }} ({{ optional($line->damageRecord->variation)->sub_sku }})</td>
                                <td>{{ @num_f($line->dispatched_quantity) }}</td>
                                <td>{{ @num_f($line->purchase_value) }}</td>
                                <td>{{ @num_f($line->compensation_amount) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if(!empty($dispatch->notes))
            <div class="row">
                <div class="col-md-12">
                    <strong>@lang('lang_v1.notes'):</strong>
                    <p>{{ $dispatch->notes }}</p>
                </div>
            </div>
        @endif
    @endcomponent
</section>

@endsection

