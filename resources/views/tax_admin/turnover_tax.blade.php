@extends('layouts.app')
@section('title', __('lang_v1.turnover_tax_report'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">{{ __('lang_v1.turnover_tax_report') }}</h1>
</section>

<section class="content">
    <div class="row no-print">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
                {!! Form::open(['url' => action([\App\Http\Controllers\ReportController::class, 'kenyaTurnoverTaxReport']), 'method' => 'get']) !!}
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('start_date', __('lang_v1.start_date') . ':') !!}
                            {!! Form::date('start_date', $start_date, ['class' => 'form-control']); !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('end_date', __('lang_v1.end_date') . ':') !!}
                            {!! Form::date('end_date', $end_date, ['class' => 'form-control']); !!}
                        </div>
                    </div>
                    <div class="col-md-3">
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
        <div class="col-md-6">
            @component('components.widget', ['class' => 'box-primary'])
                @slot('title') @lang('lang_v1.gross_sales') @endslot
                <h2><span class="display_currency" data-currency_symbol="true">{{ $gross_sales }}</span></h2>
            @endcomponent
        </div>
        <div class="col-md-6">
            @component('components.widget', ['class' => 'box-primary'])
                @slot('title') @lang('lang_v1.turnover_tax_estimate') (1.5%) @endslot
                <h2><span class="display_currency" data-currency_symbol="true">{{ $turnover_tax }}</span></h2>
            @endcomponent
        </div>
    </div>
</section>
@endsection
