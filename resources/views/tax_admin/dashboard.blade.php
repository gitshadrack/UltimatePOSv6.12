@extends('layouts.app')
@section('title', __('lang_v1.tax_dashboard'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">{{ __('lang_v1.tax_dashboard') }}</h1>
</section>

<section class="content">
    <div class="row no-print">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
                {!! Form::open(['url' => action([\App\Http\Controllers\ReportController::class, 'kenyaTaxDashboard']), 'method' => 'get']) !!}
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                            {!! Form::select('location_id', $business_locations, $location_id, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('messages.all')]); !!}
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <br>
                            <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang('report.apply_filters')</button>
                        </div>
                    </div>
                {!! Form::close() !!}
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 col-sm-6">
            @component('components.widget', ['class' => 'box-primary'])
                @slot('title') @lang('lang_v1.output_vat') @endslot
                <h3><span class="display_currency" data-currency_symbol="true">{{ $summary['output_vat'] }}</span></h3>
            @endcomponent
        </div>
        <div class="col-md-4 col-sm-6">
            @component('components.widget', ['class' => 'box-primary'])
                @slot('title') @lang('lang_v1.input_vat') @endslot
                <h3><span class="display_currency" data-currency_symbol="true">{{ $summary['input_vat'] }}</span></h3>
            @endcomponent
        </div>
        <div class="col-md-4 col-sm-6">
            @component('components.widget', ['class' => 'box-primary'])
                @slot('title') @lang('lang_v1.net_vat_payable') @endslot
                <h3><span class="display_currency" data-currency_symbol="true">{{ $summary['net_vat'] }}</span></h3>
            @endcomponent
        </div>
        <div class="col-md-4 col-sm-6">
            @component('components.widget', ['class' => 'box-primary'])
                @slot('title') @lang('lang_v1.gross_sales') @endslot
                <h3><span class="display_currency" data-currency_symbol="true">{{ $summary['gross_sales'] }}</span></h3>
            @endcomponent
        </div>
        <div class="col-md-4 col-sm-6">
            @component('components.widget', ['class' => 'box-primary'])
                @slot('title') @lang('lang_v1.turnover_tax_estimate') @endslot
                <h3><span class="display_currency" data-currency_symbol="true">{{ $summary['turnover_tax'] }}</span></h3>
            @endcomponent
        </div>
        <div class="col-md-4 col-sm-6">
            @component('components.widget', ['class' => 'box-primary'])
                @slot('title') @lang('lang_v1.etims_attention') @endslot
                <h3>{{ $summary['etims_pending'] }} @lang('lang_v1.pending') / {{ $summary['etims_failed'] }} @lang('lang_v1.failed')</h3>
            @endcomponent
        </div>
    </div>
</section>
@endsection
