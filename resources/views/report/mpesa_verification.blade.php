@extends('layouts.app')
@section('title', __('lang_v1.mpesa_verification'))

@section('content')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">{{ __('lang_v1.mpesa_verification') }}</h1>
</section>

<section class="content no-print">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
                {!! Form::open(['url' => '#', 'method' => 'get', 'id' => 'mpesa_verification_filter_form' ]) !!}
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('mpesa_user_id', __('report.user') . ':') !!}
                            {!! Form::select('mpesa_user_id', $users, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('report.all_users')]); !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('mpesa_location_id', __('purchase.business_location').':') !!}
                            {!! Form::select('mpesa_location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('messages.all')]); !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('mpesa_register_id', __('cash_register.cash_register') . ':') !!}
                            {!! Form::select('mpesa_register_id', $registers, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('report.all')]); !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('mpesa_verification_date_range', __('report.date_range') . ':') !!}
                            {!! Form::text('mpesa_verification_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'mpesa_verification_date_range', 'readonly']); !!}
                        </div>
                    </div>
                {!! Form::close() !!}
            @endcomponent
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="mpesa_verification_table">
                        <thead>
                            <tr>
                                <th>@lang('lang_v1.date') / @lang('sale.invoice_no')</th>
                                <th>@lang('report.user')</th>
                                <th>M-PESA Reference Code</th>
                                <th>@lang('sale.amount') (KES)</th>
                                <th>@lang('messages.action')</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="bg-gray font-17 footer-total text-center">
                                <td colspan="3"><strong>@lang('sale.total'):</strong></td>
                                <td><span class="display_currency" id="footer_mpesa_total" data-currency_symbol="true"></span></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>
</section>

<div class="modal fade view_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function () {
        if ($('#mpesa_verification_date_range').length == 1) {
            $('#mpesa_verification_date_range').daterangepicker({
                ranges: ranges,
                autoUpdateInput: false,
                locale: {
                    format: moment_date_format,
                    cancelLabel: LANG.clear,
                    applyLabel: LANG.apply,
                    customRangeLabel: LANG.custom_range,
                },
            });

            $('#mpesa_verification_date_range').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format(moment_date_format) + ' ~ ' + picker.endDate.format(moment_date_format));
                mpesa_verification_table.ajax.reload();
            });

            $('#mpesa_verification_date_range').on('cancel.daterangepicker', function() {
                $(this).val('');
                mpesa_verification_table.ajax.reload();
            });
        }

        var mpesa_verification_table = $('#mpesa_verification_table').DataTable({
            processing: true,
            serverSide: true,
            scrollY: '75vh',
            scrollX: true,
            scrollCollapse: true,
            fixedHeader: false,
            ajax: {
                url: '{{ action([\App\Http\Controllers\ReportController::class, 'getMpesaVerificationReport']) }}',
                data: function (d) {
                    var dateRange = $('#mpesa_verification_date_range').data('daterangepicker');
                    if ($('#mpesa_verification_date_range').val() && dateRange) {
                        d.start_date = dateRange.startDate.format('YYYY-MM-DD');
                        d.end_date = dateRange.endDate.format('YYYY-MM-DD');
                    }
                    d.user_id = $('#mpesa_user_id').val();
                    d.location_id = $('#mpesa_location_id').val();
                    d.register_id = $('#mpesa_register_id').val();
                },
            },
            columns: [
                { data: 'transaction_date_invoice', name: 't.transaction_date' },
                { data: 'cashier_name', name: 'cashier_name' },
                { data: 'mpesa_reference_code', name: 'transaction_payments.transaction_no' },
                { data: 'amount', name: 'transaction_payments.amount', searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
            footerCallback: function (row, data) {
                var total = 0;
                for (var r in data) {
                    total += $(data[r].amount).data('orig-value') ? parseFloat($(data[r].amount).data('orig-value')) : 0;
                }
                $('#footer_mpesa_total').html(__currency_trans_from_en(total));
            },
        });

        $('#mpesa_user_id, #mpesa_location_id, #mpesa_register_id').change(function () {
            mpesa_verification_table.ajax.reload();
        });

        $(document).on('click', '.update-mpesa-verification', function () {
            var btn = $(this);

            $.ajax({
                method: 'POST',
                url: btn.data('url'),
                data: {
                    status: btn.data('status'),
                    _token: $('meta[name="csrf-token"]').attr('content'),
                },
                beforeSend: function () {
                    btn.closest('.mpesa-fast-actions').find('button').prop('disabled', true);
                },
                success: function (result) {
                    if (result.success) {
                        toastr.success(result.msg);
                        mpesa_verification_table.ajax.reload(null, false);
                    } else {
                        toastr.error(result.msg);
                        btn.closest('.mpesa-fast-actions').find('button').prop('disabled', false);
                    }
                },
                error: function () {
                    toastr.error('{{ __('messages.something_went_wrong') }}');
                    btn.closest('.mpesa-fast-actions').find('button').prop('disabled', false);
                },
            });
        });
    });
</script>
@endsection
