@extends('layouts.app')
@section('title', __('damage.edit_record'))

@section('content')

<section class="content-header">
    <h1>@lang('damage.edit_record')</h1>
</section>

<section class="content no-print">
    {!! Form::open(['url' => action([\Modules\DamageManagement\Http\Controllers\DamageRecordController::class, 'update'], [$record->id]), 'method' => 'put', 'id' => 'damage_record_form']) !!}
    <div class="box box-solid">
        <div class="box-body">
            <div class="row">
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                        {!! Form::select('location_id', $locations, $record->location_id, ['class' => 'form-control select2', 'placeholder' => __('lang_v1.all')]) !!}
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('reported_at', __('messages.date') . ':*') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-calendar"></i>
                            </span>
                            {!! Form::text('reported_at', @format_datetime($record->reported_at), ['class' => 'form-control', 'id' => 'damage_reported_at', 'readonly', 'required']) !!}
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('reference_no', __('purchase.ref_no') . ':') !!}
                        {!! Form::text('reference_no', $record->reference_no, ['class' => 'form-control']) !!}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('customer_id', __('contact.customer') . ':') !!}
                        <select name="customer_id" id="damage_customer_id" class="form-control select2 damage-contact-select" data-contact-type="customer" data-placeholder="{{ __('lang_v1.none') }}" data-initial-id="{{ $record->customer_id }}" data-initial-text="{{ $customerDisplay }}" style="width:100%">
                            <option value="">{{ __('lang_v1.none') }}</option>
                        </select>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('supplier_id', __('contact.supplier') . ':') !!}
                        <select name="supplier_id" id="damage_supplier_id" class="form-control select2 damage-contact-select" data-contact-type="supplier" data-placeholder="{{ __('lang_v1.none') }}" data-initial-id="{{ $record->supplier_id }}" data-initial-text="{{ $supplierDisplay }}" style="width:100%">
                            <option value="">{{ __('lang_v1.none') }}</option>
                        </select>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('compensation_basis', __('damage.compensation_basis') . ':*') !!}
                        {!! Form::select('compensation_basis', $compensationBasis, $record->compensation_basis, ['class' => 'form-control select2', 'id' => 'damage_compensation_basis', 'required']) !!}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="box box-solid">
        <div class="box-header">
            <h3 class="box-title">@lang('damage.product_details')</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('damage_variation_id', __('product.product_name') . ':*') !!}
                        {!! Form::select('variation_id', [$record->variation_id => $record->product->name . ' (' . $record->variation->sub_sku . ')'], $record->variation_id, ['class' => 'form-control select2', 'id' => 'damage_variation_id', 'data-placeholder' => __('messages.please_select'), 'required']) !!}
                        {!! Form::hidden('product_id', $record->product_id, ['id' => 'damage_product_id']) !!}
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('quantity', __('lang_v1.quantity') . ':*') !!}
                        {!! Form::text('quantity', @format_quantity($record->quantity), ['class' => 'form-control input_number', 'id' => 'damage_quantity', 'required']) !!}
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('unit_purchase_price', __('purchase.unit_cost_before_tax') . ':') !!}
                        {!! Form::text('unit_purchase_price', @num_format($record->unit_purchase_price), ['class' => 'form-control input_number', 'id' => 'damage_unit_purchase_price']) !!}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('unit_sell_price', __('sale.unit_price') . ':') !!}
                        {!! Form::text('unit_sell_price', @num_format($record->unit_sell_price), ['class' => 'form-control input_number', 'id' => 'damage_unit_sell_price']) !!}
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('expected_compensation', __('damage.expected_compensation') . ':') !!}
                        {!! Form::text('expected_compensation', @num_format($record->expected_compensation), ['class' => 'form-control input_number', 'id' => 'damage_compensation_amount']) !!}
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="well well-sm">
                        <div class="row">
                            <div class="col-sm-4">
                                <strong>@lang('damagemanagement::damage.purchase_value'):</strong>
                                <p id="damage_total_purchase_display" class="text-success"><span class="display_currency" data-currency_symbol="true">{{ $record->purchase_value }}</span></p>
                            </div>
                            <div class="col-sm-4">
                                <strong>@lang('damagemanagement::damage.sell_value'):</strong>
                                <p id="damage_total_sell_display" class="text-info"><span class="display_currency" data-currency_symbol="true">{{ $record->sell_value }}</span></p>
                            </div>
                            <div class="col-sm-4">
                                <strong>@lang('damagemanagement::damage.compensation_value'):</strong>
                                <p id="damage_total_compensation_display" class="text-warning"><span class="display_currency" data-currency_symbol="true">{{ $record->expected_compensation }}</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-4">
                    <strong>@lang('product.brand'):</strong>
                    <p id="damage_brand_view">{{ optional($record->brand)->name ?? '-' }}</p>
                </div>
                <div class="col-sm-4">
                    <strong>@lang('product.category'):</strong>
                    <p id="damage_category_view">{{ optional($record->category)->name ?? '-' }}</p>
                </div>
                <div class="col-sm-4">
                    <strong>@lang('lang_v1.unit'):</strong>
                    <p id="damage_unit_view">{{ optional($record->unit)->short_name ?? '-' }}</p>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <div class="form-group">
                        {!! Form::label('notes', __('brand.note') . ':') !!}
                        {!! Form::textarea('notes', $record->notes, ['class' => 'form-control', 'rows' => 3]) !!}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="box box-solid">
        <div class="box-body">
            <div class="row">
                <div class="col-sm-12">
                    <button type="submit" class="btn btn-primary pull-right">@lang('messages.save')</button>
                </div>
            </div>
        </div>
    </div>
    {!! Form::close() !!}
</section>
@endsection

@section('javascript')
    <script src="{{ asset('js/damage_management_form.js?v=' . $asset_v) }}"></script>
    <script>
        $(document).ready(function () {
            $('#damage_reported_at').datetimepicker({
                format: moment_date_format + ' ' + moment_time_format,
                ignoreReadonly: true,
            });

            initDamageRecordsTable({
                searchUrl: "{{ action([\Modules\DamageManagement\Http\Controllers\DamageRecordController::class, 'searchVariations']) }}",
                detailsUrl: "{{ action([\Modules\DamageManagement\Http\Controllers\DamageRecordController::class, 'getVariationDetails'], ['id' => '__id__']) }}",
                basisSelect: '#damage_compensation_basis',
                initialRows: @json([$initialRowData])
            });

            initDamageContactSelects('.damage-contact-select');
        });
    </script>
@endsection
