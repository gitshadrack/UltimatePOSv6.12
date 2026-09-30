@extends('layouts.app')
@section('title', __('lang_v1.bulk_edit_products'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('lang_v1.bulk_edit_products')</h1>
</section>

<!-- Main content -->
<section class="content">
	<div class="row">
		<div class="col-md-8 col-md-offset-2 col-xs-12">
			<div class="form-group">
              	{!! Form::text('search_product', null, ['class' => 'form-control',
              'placeholder' => __('lang_v1.search_product_to_edit'), 'id' => 'search_product']); !!}
			</div>
		</div>
	</div>
	<br>
	{!! Form::open(['url' => action([\App\Http\Controllers\ProductController::class, 'bulkUpdate']), 
			'method' => 'post', 'id' => 'bulk_edit_products_form' ]) !!}
	<div class="box box-solid box-primary">
		<div class="box-header"><h3 class="box-title">@lang('lang_v1.bulk_edit')</h3></div>
		<div class="box-body">
			<p class="help-block">Set only the values you want to apply to every selected product, then click Apply to all.</p>
			<div class="row">
				<div class="col-md-2">
					{!! Form::label('bulk_selling_price', __('product.default_selling_price') . ' (' . __('product.inc_of_tax') . ')') !!}
					{!! Form::text('bulk_selling_price', null, ['class' => 'form-control input_number', 'id' => 'bulk_selling_price']) !!}
				</div>
				<div class="col-md-2">
					{!! Form::label('bulk_is_inactive', __('business.is_active')) !!}
					{!! Form::select('bulk_is_inactive', ['' => __('messages.please_select'), 0 => __('business.is_active'), 1 => __('lang_v1.inactive')], '', ['class' => 'form-control', 'id' => 'bulk_is_inactive']) !!}
				</div>
				<div class="col-md-2">
					{!! Form::label('bulk_not_for_selling', __('lang_v1.not_for_selling')) !!}
					{!! Form::select('bulk_not_for_selling', ['' => __('messages.please_select'), 0 => __('messages.no'), 1 => __('messages.yes')], '', ['class' => 'form-control', 'id' => 'bulk_not_for_selling']) !!}
				</div>
				<div class="col-md-2">
					{!! Form::label('bulk_enable_stock', __('product.manage_stock')) !!}
					{!! Form::select('bulk_enable_stock', ['' => __('messages.please_select'), 1 => __('messages.yes'), 0 => __('messages.no')], '', ['class' => 'form-control', 'id' => 'bulk_enable_stock']) !!}
				</div>
				<div class="col-md-2">
					{!! Form::label('bulk_alert_quantity', __('product.alert_quantity')) !!}
					{!! Form::text('bulk_alert_quantity', null, ['class' => 'form-control input_number', 'min' => 0, 'id' => 'bulk_alert_quantity']) !!}
				</div>
				<div class="col-md-2"><br><button type="button" id="apply_bulk_values" class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang('messages.update')</button></div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<table class="table text-center table-bordered" id="product_table">
				<thead id="product_table_head">
					<tr class="bg-gray">
						<th class="col-md-1">@lang('sale.product')</th>
						<th class="col-md-2">@lang('product.category')</th>
						<th class="col-md-2">@lang('product.sub_category')</th>
						<th class="col-md-2">@lang('product.brand')</th>
                		<th class="col-md-2">@lang('product.tax')</th>
                		<th class="col-md-3">@lang('business.business_locations')</th>
					</tr>
				</thead>
				@foreach($products as $product)
					@include('product.partials.bulk_edit_product_row')
				@endforeach
			</table>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white pull-right">@lang('messages.update')</button>
		</div>
	</div>
	{!! Form::close() !!}
</section>
@endsection

@section('javascript')
<script type="text/javascript">

	$(document).ready( function(){
		if ($('#search_product').length) {
		    $('#search_product').autocomplete({
	            source: function(request, response) {
	                $.getJSON(
	                    '/products/list-no-variation',
	                    {
	                        term: request.term,
	                    },
	                    response
	                );
	            },
	            minLength: 2,
	            response: function(event, ui) {
	                if (ui.content.length == 0) {
	                    toastr.error(LANG.no_products_found);
	                    $('input#search_product').select();
	                }
	            },
	            select: function(event, ui) {
	                addProductRow(ui.item.product_id);
	            },
	        }).autocomplete('instance')._renderItem = function(ul, item) {
		        var string = '<li>' + item.name + ' (' + item.sku + ')' + '</li>';
	            return $(string).appendTo(ul);
	        }
	    }
	});
	function calculateProductPrices(tr) {
		var tbody = tr.closest('tbody.product_rows')
		var tax_rate = tbody.find('select.row_tax')
            .find(':selected')
            .data('rate');
        tax_rate = tax_rate == undefined ? 0 : tax_rate;
        var purchase_exc_tax = __read_number(tr.find('input.pp_exc_tax'));
        var purchase_inc_tax = __add_percent(purchase_exc_tax, tax_rate);
        __write_number(tr.find('input.pp_inc_tax'), purchase_inc_tax);

        var profit_percent = __read_number(tr.find('input.profit_percent'));
        var selling_price = __add_percent(purchase_exc_tax, profit_percent);
        __write_number(tr.find('input.sp_exc_tax'), selling_price);

        var selling_price_inc_tax = __add_percent(selling_price, tax_rate);
        __write_number(tr.find('input.sp_inc_tax'), selling_price_inc_tax);

	}
	$(document).on('change', 'input.pp_exc_tax, input.profit_percent', function(){
		var tr = $(this).closest('tr');
		calculateProductPrices(tr);
	});
	$(document).on('change', 'select.row_tax', function(){
		var tbody = $(this).closest('tbody.product_rows');
		tbody.find('tr.variation_row').each( function(){
			calculateProductPrices($(this));
		});
	});

	$(document).on('change', 'input.pp_inc_tax', function() {
		var pp_inc_tax = __read_number($(this));
		var tr = $(this).closest('tr');
		var tbody = tr.closest('tbody.product_rows');

		var tax_rate = tbody.find('select.row_tax')
            .find(':selected')
            .data('rate');
        tax_rate = tax_rate == undefined ? 0 : tax_rate;

        var pp_exc_tax = __get_principle(pp_inc_tax, tax_rate, true);
        __write_number(tr.find('input.pp_exc_tax'), pp_exc_tax);
        tr.find('input.pp_exc_tax').change();
	});

	$(document).on('change', 'input.sp_exc_tax', function() {
		var tr = $(this).closest('tr');
		var tbody = tr.closest('tbody.product_rows');
		var tax_rate = tbody.find('select.row_tax')
            .find(':selected')
            .data('rate');
        tax_rate = tax_rate == undefined ? 0 : tax_rate;

		var sp_exc_tax = __read_number($(this));
		var purchase_exc_tax = __read_number(tr.find('input.pp_exc_tax'));
		var profit_percent = __get_rate(purchase_exc_tax, sp_exc_tax);
		__write_number(tr.find('input.profit_percent'), profit_percent);
		var selling_price_inc_tax = __add_percent(sp_exc_tax, tax_rate);
        __write_number(tr.find('input.sp_inc_tax'), selling_price_inc_tax);
	});

	$(document).on('change', 'input.sp_inc_tax', function() {
		var tr = $(this).closest('tr');
		var tbody = tr.closest('tbody.product_rows');
		var tax_rate = tbody.find('select.row_tax')
            .find(':selected')
            .data('rate');
        tax_rate = tax_rate == undefined ? 0 : tax_rate;

		var sp_inc_tax = __read_number($(this));
		var sp_exc_tax = __get_principle(sp_inc_tax, tax_rate);
		__write_number(tr.find('input.sp_exc_tax'), sp_exc_tax);

		var purchase_exc_tax = __read_number(tr.find('input.pp_exc_tax'));
		var profit_percent = __get_rate(purchase_exc_tax, sp_exc_tax);
		__write_number(tr.find('input.profit_percent'), profit_percent);
	});

	$(document).on('click', '#apply_bulk_values', function() {
		var sellingPrice = $('#bulk_selling_price').val();
		var activeState = $('#bulk_is_inactive').val();
		var sellingState = $('#bulk_not_for_selling').val();
		var stockState = $('#bulk_enable_stock').val();
		var alertQuantity = $('#bulk_alert_quantity').val();

		if (sellingPrice !== '') {
			$('input.sp_inc_tax').val(sellingPrice).trigger('change');
		}
		if (activeState !== '') $('select[name$="[is_inactive]"]').val(activeState);
		if (sellingState !== '') $('select[name$="[not_for_selling]"]').val(sellingState);
		if (stockState !== '') $('select[name$="[enable_stock]"]').val(stockState).trigger('change');
		if (alertQuantity !== '') $('input[name$="[alert_quantity]"]').val(alertQuantity);

		toastr.success('@lang('lang_v1.updated_success')');
	});

	$(document).on('change', '.bulk-enable-stock', function() {
		$(this).closest('tr').find('.bulk-alert-quantity').prop('disabled', $(this).val() === '0');
	});
	$('.bulk-enable-stock').trigger('change');

	$(document).on('change', 'select.category_id', function() {
		var cat = $(this).val();
		var tr = $(this).closest('tr');
	    $.ajax({
	        method: 'POST',
	        url: '/products/get_sub_categories',
	        dataType: 'html',
	        data: { cat_id: cat },
	        success: function(result) {
	            if (result) {
	                tr.find('select.sub_category_id').html(result);
	            }
	        },
	    });
	});

	function addProductRow(product_id) {
		if ($('#product_' + product_id).length == 0) {
			$.ajax({
		        url: '/products/get-product-to-edit/' + product_id,
		        dataType: 'html',
		        success: function(result) {
		            if (result) {
		                $(result).insertAfter('#product_table_head');
		                $('#product_' + product_id ).find('.select2').each( function() {
		                	$(this).select2();
		                });
		            }
		        },
		    });
		}
	}
</script>
@endsection
