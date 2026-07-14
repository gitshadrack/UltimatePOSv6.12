@php
    $assetVersion = session('asset_version', config('app.asset_version', 1));
    $business = session('business');
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>@lang('damage.damage_record') - {{ $record->reference_no }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css?v=' . $assetVersion) }}">
    <style>
        body {
            margin: 0;
            font-family: "Helvetica Neue", Arial, sans-serif;
            background: #fff;
            color: #1f2933;
        }
        .print-wrapper {
            max-width: 960px;
            margin: 0 auto;
            padding: 32px 40px;
        }
        .print-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #1f2933;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }
        .print-header h1 {
            margin: 0;
            font-size: 26px;
            letter-spacing: 0.35px;
        }
        .print-meta {
            text-align: right;
            font-size: 13px;
            line-height: 20px;
        }
        .section-title {
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            font-size: 15px;
            margin: 24px 0 12px;
            color: #334155;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px 24px;
            font-size: 13px;
        }
        .info-grid strong {
            display: block;
            color: #1f2933;
            font-weight: 600;
            margin-bottom: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
            font-size: 13px;
        }
        th {
            background: #1f2933;
            color: #fff;
            padding: 10px 12px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        td {
            padding: 9px 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        .totals-summary {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin-top: 24px;
        }
        .totals-card {
            flex: 1 1 240px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 16px;
            background: #f9fafb;
        }
        .totals-card strong {
            display: block;
            font-size: 13px;
            text-transform: uppercase;
            color: #475569;
            margin-bottom: 6px;
        }
        .totals-card span {
            font-size: 16px;
            font-weight: 600;
        }
        .footer-signature {
            display: flex;
            justify-content: space-between;
            margin-top: 48px;
            font-size: 12px;
        }
        .signature-block {
            width: 260px;
            padding-top: 48px;
            border-top: 1px solid #9ca3af;
            text-align: center;
        }
        @media print {
            body { margin: 0; }
            .print-wrapper { padding: 10mm 12mm; }
        }
    </style>
</head>
<body>
    <div class="print-wrapper">
        <header class="print-header">
            <div>
                <h1>@lang('damage.damage_record')</h1>
                <div style="font-size: 13px; margin-top: 6px; color: #4b5563;">
                    @lang('damage.print_reference'): {{ $record->reference_no }}
                </div>
            </div>
            <div class="print-meta">
                @if(!empty($business['name']))
                    <strong>{{ $business['name'] }}</strong><br>
                @endif
                @if(!empty($record->location))
                    {{ $record->location->name }}<br>
                    {{ implode(', ', array_filter([$record->location->city, $record->location->state, $record->location->country])) }}
                @endif
                <div style="margin-top: 8px;">
                    <strong>@lang('damage.print_date'):</strong> {{ @format_datetime($record->reported_at) }}<br>
                    <strong>@lang('damage.print_status'):</strong> @lang('damage.'.$record->dispatch_status)
                </div>
            </div>
        </header>

        <section>
            <h3 class="section-title">@lang('damage.print_summary')</h3>
            <div class="info-grid">
                <div>
                    <strong>@lang('product.product_name')</strong>
                    {{ $record->product->name ?? '-' }}
                    @if(!empty($record->variation->sub_sku))
                        <br><small>@lang('product.sku'): {{ $record->variation->sub_sku }}</small>
                    @endif
                </div>
                <div>
                    <strong>@lang('lang_v1.quantity')</strong>
                    {{ @format_quantity($record->quantity) }}
                    @if($record->dispatch_status !== 'dispatched')
                        <br><small>@lang('damage.remaining_quantity'): {{ @format_quantity($record->remaining_qty) }}</small>
                    @endif
                </div>
                <div>
                    <strong>@lang('damage.reason')</strong>
                    {{ $record->reason ?? '-' }}
                </div>
                @if(!empty($record->notes))
                <div>
                    <strong>@lang('lang_v1.notes')</strong>
                    {{ $record->notes }}
                </div>
                @endif
                @if($record->customer)
                <div>
                    <strong>@lang('contact.customer')</strong>
                    {{ $record->customer->name ?? $record->customer->supplier_business_name ?? '-' }}
                    @if(!empty($record->customer->mobile))
                        <br><small>{{ $record->customer->mobile }}</small>
                    @endif
                </div>
                @endif
                @if($record->supplier)
                <div>
                    <strong>@lang('contact.supplier')</strong>
                    {{ $record->supplier->supplier_business_name ?? $record->supplier->name ?? '-' }}
                    @if(!empty($record->supplier->mobile))
                        <br><small>{{ $record->supplier->mobile }}</small>
                    @endif
                </div>
                @endif
            </div>
        </section>

        <section>
            <h3 class="section-title">@lang('damage.compensation_details')</h3>
            <div class="info-grid">
                <div>
                    <strong>@lang('damage.purchase_value')</strong>
                    {{ @num_format($record->purchase_value) }}
                </div>
                <div>
                    <strong>@lang('damage.sell_value')</strong>
                    {{ @num_format($record->sell_value) }}
                </div>
                <div>
                    <strong>@lang('damage.compensation_basis')</strong>
                    @lang('damage.basis_'.$record->compensation_basis)
                </div>
                <div>
                    <strong>@lang('damage.expected_compensation')</strong>
                    {{ @num_format($record->expected_compensation) }}
                </div>
            </div>
        </section>

        @if($record->dispatch_status === 'dispatched' && $record->dispatchLines->count() > 0)
        <section>
            <h3 class="section-title">@lang('damage.dispatch_history')</h3>
            <table>
                <thead>
                    <tr>
                        <th>@lang('damage.dispatch_reference')</th>
                        <th>@lang('damage.print_date')</th>
                        <th>@lang('lang_v1.quantity')</th>
                        <th>@lang('damage.compensation_value')</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($record->dispatchLines as $line)
                        <tr>
                            <td>{{ $line->dispatch->reference_no ?? '-' }}</td>
                            <td>{{ @format_datetime($line->dispatch->dispatched_at) }}</td>
                            <td>{{ @format_quantity($line->dispatched_quantity) }}</td>
                            <td>{{ @num_format($line->compensation_amount) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2"></td>
                        <td><strong>{{ @format_quantity($record->dispatchLines->sum('dispatched_quantity')) }}</strong></td>
                        <td><strong>{{ @num_format($record->dispatchLines->sum('compensation_amount')) }}</strong></td>
                    </tr>
                </tfoot>
            </table>
        </section>
        @endif

        <footer class="footer-signature">
            <div class="signature-block">
                @lang('damage.print_prepared_by')
            </div>
            <div class="signature-block">
                @lang('damage.print_approved_by')
            </div>
        </footer>
    </div>
    <script>
        window.onload = function () {
            window.print();
        };
    </script>
</body>
</html>
