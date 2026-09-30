@extends('layouts.app')
@section('title', __('stock_adjustment.create_lpo_from_stock_alert'))

@section('content')
<section class="content-header">
    <br>
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('stock_adjustment.create_lpo_from_stock_alert')</h1>
</section>
<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        <p class="text-muted">@lang('stock_adjustment.stock_alert_lpo_help')</p>
        {!! Form::open(['url' => route('stock-adjustments.stock-alert-lpo.prepare'), 'method' => 'post', 'id' => 'stock_alert_lpo_form']) !!}
        <div class="row">
            <div class="col-sm-4"><div class="form-group">
                {!! Form::label('location_id', __('purchase.business_location').':*') !!}
                {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required', 'id' => 'lpo_alert_location']) !!}
            </div></div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="stock_alert_lpo_table" style="width:100%">
                <thead><tr>
                    <th><input type="checkbox" id="lpo_select_all"></th>
                    <th>@lang('sale.product')</th>
                    <th>@lang('business.location')</th>
                    <th>@lang('report.current_stock')</th>
                    <th>@lang('product.alert_quantity')</th>
                    <th>@lang('stock_adjustment.quantity_to_order')</th>
                </tr></thead>
            </table>
        </div>
        <div id="lpo_selected_items"></div>
        <div class="text-center tw-mt-4">
            <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-dw-btn-lg tw-text-white"><i class="fa fa-file-alt"></i> @lang('stock_adjustment.continue_to_lpo')</button>
        </div>
        {!! Form::close() !!}
    @endcomponent
</section>
@stop

@section('javascript')
<script src="{{ asset('js/stock_alert_lpo.js?v='.$asset_v) }}"></script>
@endsection
