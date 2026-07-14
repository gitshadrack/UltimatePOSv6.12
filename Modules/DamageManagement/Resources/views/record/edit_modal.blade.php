<div class="modal-dialog modal-lg" role="document" style="width: 95%;">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">@lang('damagemanagement::damage.edit_record')</h4>
        </div>
        <div class="modal-body">
            {!! Form::open(['url' => action([\Modules\DamageManagement\Http\Controllers\DamageRecordController::class, 'update'], [$record->id]), 'method' => 'put', 'id' => 'damage_record_form_modal']) !!}
            
            <div class="box box-solid">
                <div class="box-header">
                    <h3 class="box-title">@lang('lang_v1.basic_information')</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-4">
                            <div class="form-group">
                                {!! Form::label('location_id', __('business.business_location') . ':') !!}
                                {!! Form::select('location_id', $locations, $record->location_id, ['class' => 'form-control select2', 'placeholder' => __('lang_v1.none'), 'id' => 'modal_location_id']) !!}
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                {!! Form::label('reported_at', __('lang_v1.date') . ':*') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-calendar"></i>
                                    </span>
                                    {!! Form::text('reported_at', @format_datetime($record->reported_at), ['class' => 'form-control', 'id' => 'modal_reported_at', 'readonly', 'required']) !!}
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                {!! Form::label('reference_no', __('lang_v1.reference_no') . ':') !!}
                                {!! Form::text('reference_no', $record->reference_no, ['class' => 'form-control', 'readonly']) !!}
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4">
                            <div class="form-group">
                                {!! Form::label('customer_id', __('lang_v1.customer') . ':') !!}
                                <select name="customer_id" id="modal_customer_id" class="form-control select2 damage-contact-select" data-type="customer" data-placeholder="@lang('lang_v1.customer')" data-initial-id="{{ $record->customer_id }}" data-initial-text="{{ $customerDisplay }}">
                                    <option value="">@lang('lang_v1.none')</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                {!! Form::label('supplier_id', __('lang_v1.supplier') . ':') !!}
                                <select name="supplier_id" id="modal_supplier_id" class="form-control select2 damage-contact-select" data-type="supplier" data-placeholder="@lang('lang_v1.supplier')" data-initial-id="{{ $record->supplier_id }}" data-initial-text="{{ $supplierDisplay }}">
                                    <option value="">@lang('lang_v1.none')</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                {!! Form::label('compensation_basis', __('damagemanagement::damage.compensation_basis') . ':*') !!}
                                {!! Form::select('compensation_basis', $compensationBasis, $record->compensation_basis, ['class' => 'form-control select2', 'id' => 'modal_compensation_basis', 'required']) !!}
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="form-group">
                                {!! Form::label('notes', __('lang_v1.notes') . ':') !!}
                                {!! Form::textarea('notes', $record->notes, ['class' => 'form-control', 'rows' => 3]) !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="box box-solid">
                <div class="box-header">
                    <h3 class="box-title">@lang('damagemanagement::damage.product_details')</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                {!! Form::label('variation_id', __('product.product_name') . ':*') !!}
                                {!! Form::select('variation_id', [$record->variation_id => $record->product->name . ' (' . $record->variation->sub_sku . ')'], $record->variation_id, ['class' => 'form-control select2', 'id' => 'modal_variation_id', 'data-placeholder' => __('messages.please_select'), 'required']) !!}
                                {!! Form::hidden('product_id', $record->product_id, ['id' => 'modal_product_id']) !!}
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                {!! Form::label('quantity', __('lang_v1.quantity') . ':*') !!}
                                {!! Form::text('quantity', @format_quantity($record->quantity), ['class' => 'form-control input_number', 'id' => 'modal_quantity', 'required']) !!}
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                {!! Form::label('unit_purchase_price', __('purchase.unit_cost_before_tax') . ':') !!}
                                {!! Form::text('unit_purchase_price', @num_format($record->unit_purchase_price), ['class' => 'form-control input_number', 'id' => 'modal_unit_purchase_price']) !!}
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-3">
                            <div class="form-group">
                                {!! Form::label('unit_sell_price', __('sale.unit_price') . ':') !!}
                                {!! Form::text('unit_sell_price', @num_format($record->unit_sell_price), ['class' => 'form-control input_number', 'id' => 'modal_unit_sell_price']) !!}
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                {!! Form::label('expected_compensation', __('damagemanagement::damage.expected_compensation') . ':') !!}
                                {!! Form::text('expected_compensation', @num_format($record->expected_compensation), ['class' => 'form-control input_number', 'id' => 'modal_expected_compensation']) !!}
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="well well-sm">
                                <div class="row">
                            <div class="col-sm-4">
                                <strong>@lang('damagemanagement::damage.purchase_value'):</strong>
                                <p id="modal_total_purchase_display" class="text-success"><span class="display_currency" data-currency_symbol="true">{{ $record->purchase_value }}</span></p>
                            </div>
                            <div class="col-sm-4">
                                <strong>@lang('damagemanagement::damage.sell_value'):</strong>
                                <p id="modal_total_sell_display" class="text-info"><span class="display_currency" data-currency_symbol="true">{{ $record->sell_value }}</span></p>
                            </div>
                            <div class="col-sm-4">
                                <strong>@lang('damagemanagement::damage.compensation_value'):</strong>
                                <p id="modal_total_compensation_display" class="text-warning"><span class="display_currency" data-currency_symbol="true">{{ $record->expected_compensation }}</span></p>
                            </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="box box-solid">
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-12 text-right">
                            <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
                            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.cancel')</button>
                        </div>
                    </div>
                </div>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>

<script src="{{ asset('modules/damagemanagement/js/damage_management_form.js?v=' . $asset_v) }}"></script>
<script>
$(document).one('ready', function() {
    $('#modal_reported_at').datetimepicker({
        format: moment_date_format + ' ' + moment_time_format,
        ignoreReadonly: true
    });

    initDamageRecordForm({
        variationSelect: '#modal_variation_id',
        productIdInput: '#modal_product_id',
        unitPurchaseField: '#modal_unit_purchase_price',
        unitSellField: '#modal_unit_sell_price',
        quantityInput: '#modal_quantity',
        basisSelect: '#modal_compensation_basis',
        compensationInput: '#modal_expected_compensation',
        purchaseDisplay: '#modal_total_purchase_display',
        sellDisplay: '#modal_total_sell_display',
        compensationDisplay: '#modal_total_compensation_display',
        searchUrl: "{{ action([\Modules\DamageManagement\Http\Controllers\DamageRecordController::class, 'searchVariations']) }}",
        detailsUrl: "{{ action([\Modules\DamageManagement\Http\Controllers\DamageRecordController::class, 'getVariationDetails'], ['id' => '__id__']) }}",
        initialVariation: @json($initialRowData)
    });

    initDamageContactSelects('.damage-contact-select');
    
    // Initialize currency conversion
    __currency_convert_recursively($('.modal-dialog'));

    // Handle form submission
    $('form#damage_record_form_modal').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        
        $.ajax({
            url: $form.attr('action'),
            method: 'PUT',
            data: $form.serialize(),
            dataType: 'json',
            success: function(result) {
                if (result.success) {
                    toastr.success(result.msg);
                    $('.view_modal').modal('hide');
                    location.reload();
                } else {
                    toastr.error(result.msg);
                }
            },
            error: function(xhr) {
                toastr.error('Error updating record');
            }
        });
    });
});
</script>

