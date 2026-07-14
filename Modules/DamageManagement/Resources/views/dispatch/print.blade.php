<!DOCTYPE html>
<html>
<head>
    <title>@lang('damagemanagement::damage.dispatch_details')</title>
</head>
<body>
    <div style="text-align: center;">
        <h2>@lang('damagemanagement::damage.dispatch_print_title', ['ref' => $dispatch->reference_no])</h2>
    </div>

    <div>
        <strong>@lang('damagemanagement::damage.print_date'):</strong> {{ @format_datetime($dispatch->dispatched_at) }}<br>
        <strong>@lang('business.business_location'):</strong> {{ optional($dispatch->location)->name }}
    </div>

    <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
        <thead>
            <tr style="border-bottom: 2px solid #000;">
                <th style="text-align: left; padding: 8px;">@lang('product.product')</th>
                <th style="text-align: right; padding: 8px;">@lang('lang_v1.quantity')</th>
                <th style="text-align: right; padding: 8px;">@lang('damagemanagement::damage.purchase_value')</th>
                <th style="text-align: right; padding: 8px;">@lang('damagemanagement::damage.compensation_value')</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dispatch->lines as $line)
                <tr style="border-bottom: 1px solid #ddd;">
                    <td style="padding: 8px;">{{ optional($line->damageRecord->product)->name }} ({{ optional($line->damageRecord->variation)->sub_sku }})</td>
                    <td style="text-align: right; padding: 8px;">{{ @num_f($line->dispatched_quantity) }}</td>
                    <td style="text-align: right; padding: 8px;">{{ @num_f($line->purchase_value) }}</td>
                    <td style="text-align: right; padding: 8px;">{{ @num_f($line->compensation_amount) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="border-top: 2px solid #000; font-weight: bold;">
                <td style="padding: 8px;">@lang('lang_v1.total')</td>
                <td style="text-align: right; padding: 8px;"></td>
                <td style="text-align: right; padding: 8px;">{{ @num_f($dispatch->total_purchase_value) }}</td>
                <td style="text-align: right; padding: 8px;">{{ @num_f($dispatch->total_compensation_value) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>

