@extends('layouts.app')
@section('title', __('upgrade.z_report'))
@section('content')
<section class="content-header"><h1>@lang('upgrade.z_report')</h1></section>
<section class="content">
    <div class="box box-primary"><div class="box-body">
        <form method="GET" class="no-print form-inline">
            <div class="form-group">
                <label for="z_date">@lang('upgrade.date')</label>
                <input type="date" id="z_date" name="date" value="{{ $date }}" required class="form-control">
            </div>
            <div class="form-group">
                <label for="z_location">@lang('business.business_location')</label>
                <select id="z_location" name="location_id" class="form-control">
                    <option value="">@lang('report.all_locations')</option>
                    @foreach($locations as $id => $name)
                        <option value="{{ $id }}" @selected((string)$locationId === (string)$id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-primary" type="submit">@lang('upgrade.generate')</button>
            <button class="btn btn-default" type="button" onclick="window.print()">@lang('upgrade.print')</button>
        </form>
        <div id="z-report-print">
            <h2>{{ session('business.name') }} — @lang('upgrade.z_report')</h2>
            <p>{{ $date }} · {{ $locationId ? $locations->get($locationId) : __('report.all_locations') }}</p>
            @if($userId !== null)<p>@lang('upgrade.own_sales')</p>@endif
            <p>@lang('upgrade.z_report_help')</p>
            <table class="table table-bordered">
                <tbody>
                @foreach(['sales_count', 'sales_total', 'returns_total', 'net_sales', 'net_tax', 'expenses_total'] as $key)
                    <tr><th>@lang('upgrade.'.$key)</th><td>
                        @if($key === 'sales_count') {{ $summary[$key] }}
                        @else <span class="display_currency" data-currency_symbol="true">{{ $summary[$key] }}</span> @endif
                    </td></tr>
                @endforeach
                </tbody>
            </table>
            <h3>@lang('upgrade.payments')</h3>
            <table class="table table-bordered">
                <thead><tr><th>@lang('upgrade.payment_method')</th><th>@lang('upgrade.amount')</th></tr></thead>
                <tbody>
                @forelse($summary['payments'] as $payment)
                    <tr><td>{{ $paymentTypes[$payment->method] ?? $payment->method }}</td><td><span class="display_currency" data-currency_symbol="true">{{ $payment->total }}</span></td></tr>
                @empty
                    <tr><td colspan="2">@lang('upgrade.no_payments')</td></tr>
                @endforelse
                </tbody>
                <tfoot><tr><th>@lang('upgrade.total_collections')</th><td><span class="display_currency" data-currency_symbol="true">{{ $summary['payments']->sum('total') }}</span></td></tr></tfoot>
            </table>
        </div>
    </div></div>
</section>
@endsection
@section('css')
<style>
@media print {
    body * { visibility: hidden; }
    #z-report-print, #z-report-print * { visibility: visible; }
    #z-report-print { position: absolute; left: 0; top: 0; width: 100%; }
    #scrollable-container, .independent-scroll-layout, .independent-scroll-layout > main { overflow: visible !important; height: auto !important; }
}
</style>
@endsection
