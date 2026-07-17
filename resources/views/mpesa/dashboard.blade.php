@extends('layouts.app')
@section('title', __('lang_v1.mpesa_dashboard'))
@section('content')
<section class='content-header no-print'><h1><i class='fa fa-dashboard'></i> @lang('lang_v1.mpesa_dashboard')</h1></section>
<style>
.mpesa-filter-box{border-radius:10px;border-top:3px solid #667eea;box-shadow:0 4px 15px rgba(0,0,0,.07);margin-bottom:22px}.mpesa-filter-box .box-body{padding:18px}.mpesa-filter-box label{font-size:12px;color:#59616b}.mpesa-filter-actions{padding-top:25px;white-space:nowrap}
.mpesa-modern-card{position:relative;overflow:hidden;min-height:180px;margin-bottom:20px;padding:25px;border-radius:15px;color:#fff;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);box-shadow:0 10px 30px rgba(0,0,0,.1);transition:all .3s ease}.mpesa-modern-card:before{content:'';position:absolute;top:-50%;right:-50%;width:100%;height:100%;border-radius:50%;background:rgba(255,255,255,.1);transition:all .5s ease}.mpesa-modern-card:hover{transform:translateY(-7px);box-shadow:0 15px 40px rgba(0,0,0,.18)}.mpesa-modern-card:hover:before{top:-20%;right:-20%}
.mpesa-card-aqua{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%)}.mpesa-card-yellow{background:linear-gradient(135deg,#f093fb 0%,#f5576c 100%)}.mpesa-card-green{background:linear-gradient(135deg,#4facfe 0%,#00f2fe 100%)}.mpesa-card-red{background:linear-gradient(135deg,#fa709a 0%,#fee140 100%)}.mpesa-card-maroon{background:linear-gradient(135deg,#eb4786 0%,#ffe57f 100%)}.mpesa-card-purple{background:linear-gradient(135deg,#a8edea 0%,#fed6e3 100%);color:#333}.mpesa-card-olive{background:linear-gradient(135deg,#89f7fe 0%,#66a6ff 100%)}.mpesa-card-orange{background:linear-gradient(135deg,#ffa726 0%,#fb8c00 100%)}.mpesa-card-lime{background:linear-gradient(135deg,#43e97b 0%,#38f9d7 100%);color:#333}
.mpesa-card-icon{position:absolute;right:20px;top:20px;font-size:68px;opacity:.3;transition:all .3s ease}.mpesa-modern-card:hover .mpesa-card-icon{transform:scale(1.16) rotate(5deg);opacity:.48}.mpesa-card-value{position:relative;margin:10px 0;font-size:36px;font-weight:700;line-height:1.2}.mpesa-card-label{position:relative;font-size:13px;opacity:.9;text-transform:uppercase;letter-spacing:1px}.mpesa-card-link{position:absolute;right:25px;bottom:18px;left:25px;padding-top:12px;border-top:1px solid rgba(255,255,255,.3);color:inherit;font-size:11px;font-weight:600;opacity:.9;transition:all .2s ease}.mpesa-card-link:hover,.mpesa-card-link:focus{padding-left:8px;color:inherit;text-decoration:none;opacity:1}.mpesa-card-purple .mpesa-card-link,.mpesa-card-lime .mpesa-card-link{border-color:rgba(0,0,0,.14)}
@media(max-width:767px){.mpesa-filter-actions{padding-top:10px}.mpesa-modern-card{min-height:165px;padding:20px}.mpesa-card-value{font-size:30px}.mpesa-card-icon{font-size:55px}}
</style>
<section class='content no-print'>
 @if($migration_required)<div class='alert alert-warning'>@lang('lang_v1.mpesa_dashboard_migration_required')</div>@endif
 <div class='box box-solid mpesa-filter-box'><div class='box-body'>
  {!! Form::open(['url'=>route('mpesa.dashboard'),'method'=>'get']) !!}<div class='row'>
   <div class='col-md-4'>{!! Form::label('location_id',__('purchase.business_location').':') !!}{!! Form::select('location_id',$business_locations,$filters['location_id']??null,['class'=>'form-control select2','placeholder'=>__('lang_v1.all')]) !!}</div>
   <div class='col-md-3'>{!! Form::label('start_date',__('lang_v1.start_date').':') !!}{!! Form::date('start_date',$filters['start_date']??null,['class'=>'form-control']) !!}</div>
   <div class='col-md-3'>{!! Form::label('end_date',__('lang_v1.end_date').':') !!}{!! Form::date('end_date',$filters['end_date']??null,['class'=>'form-control']) !!}</div>
   <div class='col-md-2 mpesa-filter-actions'><button class='btn btn-primary'><i class='fa fa-filter'></i> @lang('report.apply_filters')</button> <a href='{{ route('mpesa.dashboard') }}' class='btn btn-default'><i class='fa fa-refresh'></i></a></div>
  </div>{!! Form::close() !!}
 </div></div>
 @php $cards=[
  ['total_records','mpesa_total_records','fa-list','aqua',false,'All provider records'],
  ['linked_to_sales','mpesa_linked_to_sales','fa-link','yellow',false,'Matched to POS sales'],
  ['unlinked_ready','mpesa_unlinked_ready','fa-unlink','green',false,'Available for reconciliation'],
  ['picked','mpesa_picked','fa-check-circle','red',false,'Selected for reconciliation'],
  ['pending','mpesa_pending','fa-clock','maroon',false,'Awaiting provider response'],
  ['failed_cancelled','mpesa_failed_cancelled','fa-times-circle','purple',false,'Requires attention'],
  ['linked_amount','mpesa_linked_amount','fa-money-bill-wave','olive',true,'Value linked to sales'],
  ['reversal_pending_requested','mpesa_reversal_pending_requested','fa-undo-alt','orange',false,'Queued or awaiting confirmation'],
  ['successful_reversal','mpesa_successful_reversal','fa-check-double','lime',false,'Completed reversals']
 ]; @endphp
 <div class='row'>
 @foreach($cards as $card)
  <div class='col-lg-3 col-xs-6'><div class='mpesa-modern-card mpesa-card-{{ $card[3] }}'>
   <div class='mpesa-card-icon'><i class='fa {{ $card[2] }}'></i></div>
   <div class='mpesa-card-value'>@if($card[4])<span class='display_currency' data-currency_symbol='true'>{{ $metrics[$card[0]] }}</span>@else{{ number_format($metrics[$card[0]]) }}@endif</div>
   <div class='mpesa-card-label'>{{ __('lang_v1.'.$card[1]) }}</div>
   <a href='{{ route('mpesa.records', array_merge(['metric'=>$card[0]], array_filter($filters))) }}' class='mpesa-card-link'>View {{ $card[5] }} <i class='fa fa-arrow-circle-right pull-right'></i></a>
  </div></div>
 @endforeach
 </div>
</section>
@endsection
