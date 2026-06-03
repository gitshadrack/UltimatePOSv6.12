@extends('layouts.app')

@section('title', __('lang_v1.intasend_holding_pool'))

@section('content')

<section class="content-header">
    <h1>@lang('lang_v1.intasend_holding_pool')</h1>
</section>

<section class="content">
    @if(!empty($migration_required))
        <div class="alert alert-warning">
            @lang('lang_v1.run_intasend_migration')
        </div>
    @endif

    <div class="box box-solid">
        <div class="box-header">
            <h3 class="box-title">@lang('lang_v1.filters')</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('intasend_location_id', __('purchase.business_location') . ':') !!}
                        {!! Form::select('intasend_location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width: 100%;']) !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('intasend_status', __('lang_v1.status') . ':') !!}
                        {!! Form::select('intasend_status', [
                            '' => __('lang_v1.all'),
                            'unassigned' => __('lang_v1.unassigned'),
                            'matched' => __('lang_v1.matched'),
                            'attached' => __('lang_v1.attached'),
                            'ignored' => __('lang_v1.ignored'),
                        ], null, ['class' => 'form-control select2', 'style' => 'width: 100%;']) !!}
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <a href="{{ action([\App\Http\Controllers\IntaSendController::class, 'settings']) }}" class="btn btn-primary" style="margin-top: 24px;">
                        @lang('lang_v1.intasend_settings')
                    </a>
                    <a href="{{ action([\App\Http\Controllers\IntaSendController::class, 'collections']) }}" class="btn btn-default" style="margin-top: 24px;">
                        @lang('lang_v1.intasend_collections_report')
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="box box-solid">
        <div class="box-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="intasend_payments_table">
                    <thead>
                        <tr>
                            <th>@lang('messages.date')</th>
                            <th>@lang('lang_v1.transaction_code')</th>
                            <th>@lang('lang_v1.till_or_paybill_number')</th>
                            <th>@lang('purchase.business_location')</th>
                            <th>@lang('contact.customer')</th>
                            <th>@lang('lang_v1.phone_number')</th>
                            <th>@lang('sale.amount')</th>
                            <th>@lang('lang_v1.status')</th>
                            <th>@lang('lang_v1.match_reason')</th>
                            <th>@lang('lang_v1.match_note')</th>
                            <th>@lang('messages.action')</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="attach_intasend_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            {!! Form::open(['url' => '#', 'method' => 'post', 'id' => 'attach_intasend_form']) !!}
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="@lang('messages.close')"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">@lang('lang_v1.link_intasend_payment')</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    {!! Form::label('intasend_contact_id', __('contact.customer') . ':') !!}
                    {!! Form::select('contact_id', $customers, null, ['class' => 'form-control select2', 'id' => 'intasend_contact_id', 'style' => 'width: 100%;', 'placeholder' => __('messages.please_select')]) !!}
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                <button type="submit" class="btn btn-primary">@lang('lang_v1.link_payment')</button>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>

@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function () {
        var intasend_table = $('#intasend_payments_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ action([\App\Http\Controllers\IntaSendController::class, 'pool']) }}',
                data: function (d) {
                    d.location_id = $('#intasend_location_id').val();
                    d.status = $('#intasend_status').val();
                }
            },
            columns: [
                { data: 'created_at', name: 'intasend_payments.created_at' },
                { data: 'transaction_code', name: 'intasend_payments.transaction_code' },
                { data: 'till_number', name: 'intasend_payments.till_number' },
                { data: 'location_name', name: 'bl.name' },
                { data: 'customer', name: 'c.name', orderable: false },
                { data: 'phone_number', name: 'intasend_payments.phone_number' },
                { data: 'amount', name: 'intasend_payments.amount' },
                { data: 'reconciliation_status', name: 'intasend_payments.reconciliation_status' },
                { data: 'match_reason', name: 'intasend_payments.match_reason' },
                { data: 'match_note', name: 'intasend_payments.match_note', orderable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
            aaSorting: [[0, 'desc']]
        });

        $('#intasend_location_id, #intasend_status').change(function () {
            intasend_table.ajax.reload();
        });

        $(document).on('click', '.attach-intasend-payment', function () {
            var id = $(this).data('id');
            $('#attach_intasend_form').attr('action', '{{ url('/intasend/payments') }}/' + id + '/attach');
            $('#attach_intasend_modal').modal('show');
        });

        $('#attach_intasend_form').on('submit', function (e) {
            e.preventDefault();
            var form = $(this);
            $.ajax({
                method: 'POST',
                url: form.attr('action'),
                data: form.serialize(),
                success: function (result) {
                    if (result.success) {
                        toastr.success(result.msg);
                        $('#attach_intasend_modal').modal('hide');
                        intasend_table.ajax.reload(null, false);
                    } else {
                        toastr.error(result.msg);
                    }
                }
            });
        });
    });
</script>
@endsection
