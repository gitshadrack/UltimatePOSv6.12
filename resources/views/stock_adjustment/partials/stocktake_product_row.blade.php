<tr class="stocktake_product_row">
    <td>
        {{ $product->product_name }}<br><small class="text-muted">{{ $product->sub_sku }}</small>
        <input type="hidden" name="products[{{ $row_index }}][product_id]" value="{{ $product->product_id }}">
        <input type="hidden" name="products[{{ $row_index }}][variation_id]" value="{{ $product->variation_id }}" class="stocktake_variation_id">
    </td>
    <td class="text-center">
        <span class="stocktake_system_display">{{ $product->formatted_qty_available }}</span> {{ $product->unit }}
        <input type="hidden" class="stocktake_system_quantity" value="{{ $product->qty_available }}">
    </td>
    <td>
        <input type="text" name="products[{{ $row_index }}][physical_quantity]" value="{{ $product->formatted_qty_available }}"
            class="form-control input_number stocktake_physical_quantity" required data-rule-min-value="0"
            @if($product->unit_allow_decimal != 1) data-rule-abs_digit="true" data-decimal="0" @else data-decimal="1" @endif>
    </td>
    <td class="text-center"><span class="stocktake_difference">0</span> {{ $product->unit }}</td>
    <td class="text-center"><i class="fa fa-trash remove_stocktake_row cursor-pointer" aria-hidden="true"></i></td>
</tr>
