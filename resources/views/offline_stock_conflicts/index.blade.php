@extends('layouts.app')

@section('title', 'Offline Stock Conflict Review')

@section('content')
<section class="content-header">
    <h1>Offline Stock Conflict Review</h1>
</section>
<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr><th>Invoice</th><th>Offline time</th><th>Cashier</th><th>Customer</th><th>Location</th><th>Conflict details</th><th>Resolution</th></tr>
                </thead>
                <tbody>
                @forelse($conflicts as $sale)
                    <tr>
                        <td>{{ $sale->invoice_no }}</td>
                        <td>{{ $sale->offline_created_at }}</td>
                        <td>{{ optional($sale->sales_person)->first_name }} {{ optional($sale->sales_person)->last_name }}</td>
                        <td>{{ optional($sale->contact)->name }}</td>
                        <td>{{ optional($sale->location)->name }}</td>
                        <td><pre style="white-space:pre-wrap;max-width:340px">{{ $sale->offline_sync_note }}</pre></td>
                        <td style="min-width:310px">
                            @can('stock_adjustment.create')
                                <form method="POST" action="{{ route('offline-stock-conflicts.approve', $sale->id) }}" style="display:inline" onsubmit="return confirm('Create an automatic inbound stock correction for this deficit?')">
                                    @csrf
                                    <button class="btn btn-sm btn-success">Approve &amp; Force Adjustment</button>
                                </form>
                                <form method="POST" action="{{ route('offline-stock-conflicts.reassign', $sale->id) }}" style="margin-top:8px">
                                    @csrf
                                    <div class="input-group">
                                        <select name="source_location_id" class="form-control input-sm" required>
                                            <option value="">Source location</option>
                                            @foreach($locations as $locationId => $locationName)
                                                @if($locationId != $sale->location_id)<option value="{{ $locationId }}">{{ $locationName }}</option>@endif
                                            @endforeach
                                        </select>
                                        <span class="input-group-btn"><button class="btn btn-sm btn-warning">Reassign Stock</button></span>
                                    </div>
                                </form>
                            @endcan
                            @if(auth()->user()->hasAnyPermission(['sell.delete', 'direct_sell.delete', 'access_sell_return']))
                                <form method="POST" action="{{ route('offline-stock-conflicts.void', $sale->id) }}" style="margin-top:8px" onsubmit="return confirm('Void this sale and create a full customer credit note?')">
                                    @csrf
                                    <button class="btn btn-sm btn-danger">Cancel / Void &amp; Credit</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">No offline stock conflicts require review.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @endcomponent
</section>
@endsection
