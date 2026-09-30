<tbody class="product_rows" id="product_{{$product->id}}">
	<tr class="bg-green">
		<td>{{$product->name}} ({{$product->sku}})</td>
		<td>
			{!! Form::select('products[' . $product->id . '][category_id]', $categories, $product->category_id, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2 input-sm category_id', 'style' => 'width: 100%;']); !!}
		</td>
		<td>
			{!! Form::select('products[' . $product->id . '][sub_category_id]', !empty($sub_categories[$product->category_id]) ? $sub_categories[$product->category_id] : [], $product->sub_category_id, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2 input-sm sub_category_id', 'style' => 'width: 100%;']); !!}
		</td>
		<td>
			{!! Form::select('products[' . $product->id . '][brand_id]', $brands, $product->brand_id, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2 input-sm', 'style' => 'width: 100%;']); !!}
		</td>
		<td>
			{!! Form::select('products[' . $product->id . '][tax]', $taxes, $product->tax, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2 input-sm row_tax', 'style' => 'width: 100%;'],$tax_attributes); !!}
		</td>
		<td>
			{!! Form::select('products[' . $product->id . '][product_locations][]', $business_locations, $product->product_locations->pluck('id'), ['class' => 'form-control select2', 'multiple']); !!}
		</td>
	<tr>
	<tr>
		<td colspan="2">
			{!! Form::label('products_' . $product->id . '_is_inactive', __('business.is_active')) !!}
			{!! Form::select('products[' . $product->id . '][is_inactive]', [0 => __('business.is_active'), 1 => __('lang_v1.inactive')], (int) $product->is_inactive, ['class' => 'form-control input-sm', 'id' => 'products_' . $product->id . '_is_inactive']); !!}
		</td>
		<td colspan="2">
			{!! Form::label('products_' . $product->id . '_not_for_selling', __('lang_v1.not_for_selling')) !!}
			{!! Form::select('products[' . $product->id . '][not_for_selling]', [0 => __('messages.no'), 1 => __('messages.yes')], (int) $product->not_for_selling, ['class' => 'form-control input-sm', 'id' => 'products_' . $product->id . '_not_for_selling']); !!}
		</td>
		<td>
			{!! Form::label('products_' . $product->id . '_enable_stock', __('product.manage_stock')) !!}
			{!! Form::select('products[' . $product->id . '][enable_stock]', [1 => __('messages.yes'), 0 => __('messages.no')], (int) $product->enable_stock, ['class' => 'form-control input-sm bulk-enable-stock', 'id' => 'products_' . $product->id . '_enable_stock']); !!}
		</td>
		<td>
			{!! Form::label('products_' . $product->id . '_alert_quantity', __('product.alert_quantity')) !!}
			{!! Form::text('products[' . $product->id . '][alert_quantity]', !is_null($product->alert_quantity) ? @format_quantity($product->alert_quantity) : null, ['class' => 'form-control input-sm input_number bulk-alert-quantity', 'min' => 0, 'id' => 'products_' . $product->id . '_alert_quantity']); !!}
		</td>
	</tr>
	<tr>
		<td colspan="6">
			<table class="table">
				<thead>
					<tr>
						<th>@lang('lang_v1.variation')</th>
						<th>@lang('product.default_purchase_price')</th>
						<th>@lang('product.profit_percent') @show_tooltip(__('tooltip.profit_percent'))</th>
                		<th>@lang('product.default_selling_price')</th>
                		<th>@lang('lang_v1.group_price')</th>
					</tr>
				</thead>
				<tbody>
				@foreach($product->variations as $variation)
					<tr class="variation_row">
						@include('product.partials.bulk_edit_variation_row')
					</tr>
				@endforeach
				</tbody>
			</table>
		</td>
	</tr>
</tbody>
