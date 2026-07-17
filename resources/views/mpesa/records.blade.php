@extends('layouts.app')
@section('title', $metric_title)

@section('content')
<section class='content-header no-print'>
 <h1><i class='fa fa-list'></i> {{ $metric_title }}</h1>
 <p class='text-muted'>M-PESA records represented by this dashboard card.</p>
</section>
<section class='content no-print'>
 <div class='box box-solid box-primary'><div class='box-body'>
  {!! Form::open(['url'=>route('mpesa.records', ['metric'=>$metric]),'method'=>'get']) !!}<div class='row'>
   <div class='col-md-4'>{!! Form::label('location_id',__('purchase.business_location').':') !!}{!! Form::select('location_id',$business_locations,$filters['location_id']??null,['class'=>'form-control select2','placeholder'=>__('lang_v1.all')]) !!}</div>
   <div class='col-md-3'>{!! Form::label('start_date',__('lang_v1.start_date').':') !!}{!! Form::date('start_date',$filters['start_date']??null,['class'=>'form-control']) !!}</div>
   <div class='col-md-3'>{!! Form::label('end_date',__('lang_v1.end_date').':') !!}{!! Form::date('end_date',$filters['end_date']??null,['class'=>'form-control']) !!}</div>
   <div class='col-md-2' style='padding-top:25px'><button class='btn btn-primary'><i class='fa fa-filter'></i> @lang('report.apply_filters')</button></div>
  </div>{!! Form::close() !!}
 </div></div>

 <div class='box box-primary'>
  <div class='box-header with-border'><h3 class='box-title'>{{ $metric_title }}</h3><div class='box-tools'><a href='{{ route('mpesa.dashboard', request()->only(['location_id','start_date','end_date'])) }}' class='btn btn-default btn-sm'><i class='fa fa-arrow-left'></i> Dashboard</a></div></div>
  <div class='box-body table-responsive'>
   <table class='table table-bordered table-striped'>
    <thead><tr><th>@lang('messages.date')</th><th>Provider</th><th>@lang('purchase.business_location')</th><th>@lang('lang_v1.mpesa_transaction_no')</th><th>@lang('lang_v1.phone_number')</th><th>@lang('sale.status')</th><th>Reconciliation</th><th>Linked Sale</th><th class='text-right'>@lang('sale.amount')</th><th>Reversal</th></tr></thead>
    <tbody>
    @forelse($records as $record)
     <tr>
      <td>{{ @format_datetime($record->created_at) }}</td><td>{{ $record->provider }}</td><td>{{ $record->location_name ?: '--' }}</td><td><strong>{{ $record->transaction_code ?: '--' }}</strong></td><td>{{ $record->phone_number ?: '--' }}</td>
      <td><span class='label {{ strtoupper($record->status)==='COMPLETE'?'label-success':(strtoupper($record->status)==='PENDING'?'label-warning':'label-danger') }}'>{{ $record->status ?: '--' }}</span></td>
      <td>{{ $record->reconciliation_status ?: '--' }}</td><td>@if($record->invoice_no)<span class='label label-success'>{{ $record->invoice_no }}</span>@else -- @endif</td>
      <td class='text-right'><span class='display_currency' data-currency_symbol='true'>{{ $record->amount }}</span></td><td>{{ $record->reversal_status ?: 'none' }}</td>
     </tr>
    @empty
     <tr><td colspan='10' class='text-center text-muted'>No M-PESA records found for this card and filter selection.</td></tr>
    @endforelse
    </tbody>
   </table>
   <div class='text-right'>{{ $records->links() }}</div>
  </div>
 </div>
</section>
@endsection
