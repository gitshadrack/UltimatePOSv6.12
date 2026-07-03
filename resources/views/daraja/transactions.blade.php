@extends('layouts.app')
@section('title', __('lang_v1.daraja_transactions'))

@section('content')
<section class="content-header"><h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('lang_v1.daraja_transactions')</h1></section>
<section class="content">
    @if($migration_required)<div class="alert alert-warning">@lang('lang_v1.run_daraja_migration')</div>@endif
    <div class="box box-primary"><div class="box-body">
        <div class="row">
            <div class="col-md-4">{!! Form::select('daraja_location_id', $business_locations, null, ['class' => 'form-control select2', 'id' => 'daraja_location_id']) !!}</div>
            <div class="col-md-3">{!! Form::select('daraja_source', ['' => __('messages.all'), 'stk' => 'STK', 'c2b' => 'C2B'], null, ['class' => 'form-control', 'id' => 'daraja_source']) !!}</div>
            <div class="col-md-3">{!! Form::select('daraja_status', ['' => __('messages.all'), 'PENDING' => 'PENDING', 'COMPLETE' => 'COMPLETE', 'FAILED' => 'FAILED'], null, ['class' => 'form-control', 'id' => 'daraja_status']) !!}</div>
        </div><br>
        <div class="table-responsive"><table class="table table-bordered table-striped" id="daraja_transactions_table">
            <thead><tr><th>@lang('messages.date')</th><th>@lang('purchase.business_location')</th><th>@lang('lang_v1.source')</th><th>@lang('lang_v1.mpesa_transaction_no')</th><th>@lang('lang_v1.phone_number')</th><th>@lang('sale.amount')</th><th>@lang('sale.status')</th><th>@lang('contact.customer')</th><th>@lang('messages.action')</th></tr></thead>
        </table></div>
    </div></div>
</section>

<div class="modal fade" id="attach_daraja_modal"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4>@lang('lang_v1.link_payment')</h4></div><div class="modal-body">{!! Form::select('daraja_contact_id', $customers, null, ['class' => 'form-control select2', 'id' => 'daraja_contact_id', 'style' => 'width:100%']) !!}</div><div class="modal-footer"><button class="btn btn-primary" id="confirm_attach_daraja">@lang('messages.save')</button></div></div></div></div>
@endsection

@section('javascript')
<script>
var darajaAttachId = null;
var darajaTable = $('#daraja_transactions_table').DataTable({processing:true, serverSide:true, ajax:{url:'{{ route('daraja.transactions') }}', data:function(d){d.location_id=$('#daraja_location_id').val();d.source=$('#daraja_source').val();d.status=$('#daraja_status').val();}}, columns:[{data:'created_at'},{data:'location_name'},{data:'source'},{data:'transaction_code'},{data:'phone_number'},{data:'amount'},{data:'status'},{data:'contact_name'},{data:'action',orderable:false,searchable:false}]});
$('#daraja_location_id,#daraja_source,#daraja_status').change(function(){darajaTable.ajax.reload();});
$(document).on('click','.attach-daraja-payment',function(){darajaAttachId=$(this).data('id');$('#attach_daraja_modal').modal('show');});
$('#confirm_attach_daraja').click(function(){var button=$(this);button.prop('disabled',true);$.post('{{ url('/daraja/payments') }}/'+darajaAttachId+'/attach',{contact_id:$('#daraja_contact_id').val()}).done(function(r){toastr[r.success?'success':'error'](r.msg);if(r.success){$('#attach_daraja_modal').modal('hide');darajaTable.ajax.reload(null,false);}}).always(function(){button.prop('disabled',false);});});
</script>
@endsection
