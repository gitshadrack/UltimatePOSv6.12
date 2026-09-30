@extends('layouts.app')
@section('title', __('stock_adjustment.new_stocktake'))

@section('content')
<section class="content-header">
    <br>
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('stock_adjustment.new_stocktake')</h1>
</section>

<section class="content no-print">
    {!! Form::open(['url' => route('stock-adjustments.stocktake.store'), 'method' => 'post', 'id' => 'stocktake_form']) !!}
    @component('components.widget', ['class' => 'box-solid'])
        <p class="text-muted">@lang('stock_adjustment.stocktake_help')</p>
        <div class="row">
            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label('location_id', __('purchase.business_location').':*') !!}
                    {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required']) !!}
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label('transaction_date', __('messages.date').':*') !!}
                    <div class="input-group"><span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                        {!! Form::text('transaction_date', @format_datetime('now'), ['class' => 'form-control', 'readonly', 'required']) !!}
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label('stocktake_reference', __('purchase.ref_no').':') !!}
                    {!! Form::text('stocktake_reference', null, ['class' => 'form-control', 'placeholder' => __('stock_adjustment.stocktake')]) !!}
                </div>
            </div>
        </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-solid'])
        <div class="row"><div class="col-sm-8 col-sm-offset-2"><div class="form-group">
            <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-search"></i></span>
                {!! Form::text('search_product', null, ['class' => 'form-control', 'id' => 'stocktake_search_product', 'placeholder' => __('stock_adjustment.search_products'), 'disabled']) !!}
                <span class="input-group-btn"><button type="button" class="btn btn-primary" id="stocktake_scan_button" disabled><i class="fa fa-camera"></i> @lang('stock_adjustment.scan_barcode')</button></span>
            </div>
        </div></div></div>
        <input type="hidden" id="stocktake_row_index" value="0">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-condensed" id="stocktake_product_table">
                <thead><tr>
                    <th>@lang('sale.product')</th>
                    <th class="text-center">@lang('stock_adjustment.system_quantity')</th>
                    <th class="text-center">@lang('stock_adjustment.physical_quantity')</th>
                    <th class="text-center">@lang('stock_adjustment.difference')</th>
                    <th></th>
                </tr></thead>
                <tbody></tbody>
            </table>
        </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-solid'])
        <div class="row">
            <div class="col-sm-6"><div class="form-group">
                {!! Form::label('additional_notes', __('stock_adjustment.reason_for_stock_adjustment').':') !!}
                {!! Form::textarea('additional_notes', null, ['class' => 'form-control', 'rows' => 3]) !!}
            </div></div>
        </div>
        <div class="text-center"><button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-dw-btn-lg tw-text-white">@lang('messages.save')</button></div>
    @endcomponent
    {!! Form::close() !!}
</section>

<div class="modal fade" id="stocktake_scanner_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document"><div class="modal-content">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">@lang('stock_adjustment.scan_barcode')</h4></div>
        <div class="modal-body"><div id="stocktake_scanner" style="width:100%"></div><p id="stocktake_scanner_status" class="text-muted text-center"></p></div>
        <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button></div>
    </div></div>
</div>
@stop

@section('javascript')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="{{ asset('js/stocktake.js?v='.$asset_v) }}"></script>
<script>__page_leave_confirmation('#stocktake_form');</script>
@endsection
