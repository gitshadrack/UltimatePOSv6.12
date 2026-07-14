@extends('layouts.app')
@section('title', __('damagemanagement::damage.add_record'))

@section('content')

<section class="content-header">
    <h1>@lang('damagemanagement::damage.add_record')</h1>
</section>

<section class="content no-print">
    {!! Form::open(['url' => action([\Modules\DamageManagement\Http\Controllers\DamageRecordController::class, 'store']), 'method' => 'post', 'id' => 'damage_record_form']) !!}
    
    <div class="box box-solid">
        <div class="box-header">
            <h3 class="box-title">@lang('lang_v1.basic_information')</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('location_id', __('business.business_location') . ':') !!}
                        {!! Form::select('location_id', $locations, null, ['class' => 'form-control select2', 'placeholder' => __('lang_v1.none')]) !!}
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('reported_at', __('lang_v1.date') . ':*') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-calendar"></i>
                            </span>
                            {!! Form::text('reported_at', @format_datetime('now'), ['class' => 'form-control', 'readonly', 'required']) !!}
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('reference_no', __('lang_v1.reference_no') . ':') !!}
                        {!! Form::text('reference_no', null, ['class' => 'form-control']) !!}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('customer_id', __('lang_v1.customer') . ':') !!}
                        <select name="customer_id" class="form-control select2 damage-contact-select" data-type="customer" data-placeholder="@lang('lang_v1.customer')">
                            <option value="">@lang('lang_v1.none')</option>
                        </select>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('supplier_id', __('lang_v1.supplier') . ':') !!}
                        <select name="supplier_id" class="form-control select2 damage-contact-select" data-type="supplier" data-placeholder="@lang('lang_v1.supplier')">
                            <option value="">@lang('lang_v1.none')</option>
                        </select>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('compensation_basis', __('damagemanagement::damage.compensation_basis') . ':*') !!}
                        {!! Form::select('compensation_basis', $compensationBasis, null, ['class' => 'form-control select2', 'required', 'id' => 'damage_compensation_basis']) !!}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <div class="form-group">
                        {!! Form::label('notes', __('lang_v1.notes') . ':') !!}
                        {!! Form::textarea('notes', null, ['class' => 'form-control', 'rows' => 3]) !!}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="box box-solid">
        <div class="box-header">
            <h3 class="box-title">@lang('damagemanagement::damage.products')</h3>
        </div>
        <div class="box-body">
            <!-- Global Product Search Box -->
            <div class="row">
                <div class="col-sm-12">
                    <div class="form-group">
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-search"></i>
                            </span>
                            {!! Form::text('global_search_product', null, ['class' => 'form-control', 'id' => 'global_damage_product_search', 'placeholder' => __('lang_v1.search_product_placeholder')]) !!}
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-primary" id="add_product_from_search">
                                    <i class="fa fa-plus"></i> @lang('product.add')
                                </button>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered" id="damage_products_table">
                    <thead>
                        <tr>
                            <th>@lang('product.product_name')</th>
                            <th>@lang('lang_v1.quantity')</th>
                            <th>@lang('purchase.unit_cost_before_tax')</th>
                            <th>@lang('sale.unit_price')</th>
                            <th>@lang('damagemanagement::damage.compensation_value')</th>
                            <th>@lang('messages.action')</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="2" class="text-right">@lang('sale.total')</th>
                            <th class="text-right"><span id="damage_total_purchase_display">0.00</span></th>
                            <th class="text-right"><span id="damage_total_sell_display">0.00</span></th>
                            <th class="text-right"><span id="damage_total_compensation_display">0.00</span></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <button type="button" class="btn btn-primary" id="add_damage_product_row">
                <i class="fa fa-plus"></i> @lang('product.add_product')
            </button>
        </div>
    </div>

    <div class="box box-solid">
        <div class="box-body">
            <div class="row">
                <div class="col-sm-12 text-right">
                    <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
                    <a href="{{action([\Modules\DamageManagement\Http\Controllers\DamageRecordController::class, 'index'])}}" 
                       class="btn btn-default">@lang('messages.cancel')</a>
                </div>
            </div>
        </div>
    </div>
    {!! Form::close() !!}
</section>

<!-- Hidden row template -->
<script type="text/template" id="damage_product_row_template">
<tr class="damage-row">
    <td>
        <select class="form-control damage-variation-select" name="products[__index__][variation_id]" data-placeholder="@lang('lang_v1.search_product')">
            <option value="">@lang('lang_v1.search_product')</option>
        </select>
        <input type="hidden" class="damage-product-id" name="products[__index__][product_id]">
    </td>
    <td>
        <input type="text" class="form-control input_number damage-quantity" name="products[__index__][quantity]" placeholder="0.00">
    </td>
    <td>
        <input type="text" class="form-control input_number damage-unit-purchase" name="products[__index__][unit_purchase_price]" placeholder="0.00">
    </td>
    <td>
        <input type="text" class="form-control input_number damage-unit-sell" name="products[__index__][unit_sell_price]" placeholder="0.00">
    </td>
    <td>
        <input type="text" class="form-control input_number damage-compensation" name="products[__index__][expected_compensation]" placeholder="0.00">
    </td>
    <td>
        <button type="button" class="btn btn-xs btn-danger remove-damage-row">
            <i class="fa fa-trash"></i>
        </button>
    </td>
</tr>
</script>

@endsection

@section('javascript')
    <script src="{{ asset('modules/damagemanagement/js/damage_records_form.js?v=' . $asset_v) }}"></script>
    <script type="text/javascript">
        // Set language variables
        if (typeof LANG === 'undefined') LANG = {};
        LANG.damage_no_records_selected = @json(__('damagemanagement::damage.damage_no_records_selected'));
        
        $(document).ready(function() {
            // Initialize datetime picker
            $('input[name="reported_at"]').datetimepicker({
                format: moment_date_format + ' ' + moment_time_format,
                ignoreReadonly: true
            });

            var searchUrl = "{{ action([\Modules\DamageManagement\Http\Controllers\DamageRecordController::class, 'searchVariations']) }}";
            var detailsUrl = "{{ action([\Modules\DamageManagement\Http\Controllers\DamageRecordController::class, 'getVariationDetails'], ['id' => '__id__']) }}";

            // Initialize product rows
            initDamageRecordsTable({
                searchUrl: searchUrl,
                detailsUrl: detailsUrl,
                basisSelect: '#damage_compensation_basis',
                initialRows: []
            });

            // Initialize contact selects
            initDamageContactSelects('.damage-contact-select');
        });
    </script>
@endsection
