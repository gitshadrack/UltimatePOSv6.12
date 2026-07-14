@extends('layouts.app')
@section('title', __('damagemanagement::damage.records_list'))

@section('content')

<section class="content-header no-print">
    <h1>@lang('damagemanagement::damage.records_list')</h1>
</section>

<section class="content no-print">
    @component('components.filters', ['title' => __('report.filters')])
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('damage_record_filter_location', __('business.location') . ':') !!}
                {!! Form::select('damage_record_filter_location', $locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]) !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('damage_record_filter_brand', __('product.brand') . ':') !!}
                {!! Form::select('damage_record_filter_brand', $brands, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]) !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('damage_record_filter_category', __('product.category') . ':') !!}
                {!! Form::select('damage_record_filter_category', $categories, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]) !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('damage_record_filter_status', __('damagemanagement::damage.dispatch_status') . ':') !!}
                {!! Form::select('damage_record_filter_status', $dispatchStatuses, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]) !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('damage_record_filter_date_range', __('report.date_range') . ':') !!}
                {!! Form::text('damage_record_filter_date_range', null, ['class' => 'form-control', 'readonly', 'placeholder' => __('lang_v1.select_a_date_range')]) !!}
            </div>
        </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary', 'title' => __('damagemanagement::damage.records_list')])
        @slot('tool')
            @can('damage_record.create')
                <div class="box-tools">
                    <a class="btn btn-block btn-primary" href="{{ action([\Modules\DamageManagement\Http\Controllers\DamageRecordController::class, 'create']) }}">
                        <i class="fa fa-plus"></i> @lang('damagemanagement::damage.add_record')
                    </a>
                </div>
            @endcan
        @endslot
        <div class="table-responsive">
            <table class="table table-bordered table-striped ajax_view" id="damage_record_table">
                <thead>
                    <tr>
                        <th>@lang('messages.action')</th>
                        <th>@lang('purchase.ref_no')</th>
                        <th>@lang('lang_v1.date')</th>
                        <th>@lang('product.product')</th>
                        <th>@lang('business.location')</th>
                        <th>@lang('lang_v1.quantity')</th>
                        <th>@lang('damagemanagement::damage.dispatched_qty')</th>
                        <th>@lang('damagemanagement::damage.remaining_qty')</th>
                        <th>@lang('damagemanagement::damage.purchase_value')</th>
                        <th>@lang('damagemanagement::damage.sell_value')</th>
                        <th>@lang('damagemanagement::damage.expected_compensation')</th>
                        <th>@lang('damagemanagement::damage.dispatch_status')</th>
                        <th>@lang('damagemanagement::damage.approval_status')</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr class="bg-gray text-right">
                        <th colspan="5"></th>
                        <th class="text-right" id="record_footer_quantity">0</th>
                        <th class="text-right" id="record_footer_dispatched_qty">0</th>
                        <th class="text-right" id="record_footer_unapprove_qty">0</th>
                        <th class="text-right" id="record_footer_purchase">0</th>
                        <th class="text-right" id="record_footer_sell">0</th>
                        <th class="text-right" id="record_footer_compensation">0</th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endcomponent

    <div class="modal fade view_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
</section>
@endsection

@section('javascript')
    <script type="text/javascript">
    $(document).ready(function() {
        var damage_record_table = $('#damage_record_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{action([\Modules\DamageManagement\Http\Controllers\DamageRecordController::class, "index"])}}',
                data: function(d) {
                    d.location_id = $('#damage_record_filter_location').val();
                    d.brand_id = $('#damage_record_filter_brand').val();
                    d.category_id = $('#damage_record_filter_category').val();
                    d.dispatch_status = $('#damage_record_filter_status').val();
                    d.start_date = $('#damage_record_filter_date_range').data('start_date');
                    d.end_date = $('#damage_record_filter_date_range').data('end_date');
                }
            },
            columns: [
                {data: 'action', name: 'action', orderable: false, searchable: false},
                {data: 'reference_no', name: 'reference_no'},
                {data: 'reported_at', name: 'reported_at'},
                {data: 'product_display', name: 'product_display'},
                {data: 'location_name', name: 'location_name'},
                {data: 'quantity', name: 'quantity'},
                {data: 'dispatched_qty', name: 'dispatched_qty'},
                {data: 'remaining_qty', name: 'remaining_qty'},
                {data: 'purchase_value', name: 'purchase_value'},
                {data: 'sell_value', name: 'sell_value'},
                {data: 'expected_compensation', name: 'expected_compensation'},
                {data: 'dispatch_status_label', name: 'dispatch_status', orderable: false, searchable: false},
                {data: 'approval_status_label', name: 'approval_status', orderable: false, searchable: false}
            ],
            fnDrawCallback: function() {
                var api = this.api();
                
                // Calculate quantity totals
                var quantity = api.column(5, {page: 'current'}).data().reduce(function(a, b) {
                    var num = parseFloat(b) || 0;
                    return a + num;
                }, 0);
                
                var dispatchedQty = api.column(6, {page: 'current'}).data().reduce(function(a, b) {
                    var num = parseFloat(b) || 0;
                    return a + num;
                }, 0);
                
                var unapproveQty = api.column(7, {page: 'current'}).data().reduce(function(a, b) {
                    var num = parseFloat(b) || 0;
                    return a + num;
                }, 0);
                
                // Calculate currency totals
                var purchase = api.column(8, {page: 'current'}).data().reduce(function(a, b) {
                    // Extract numeric value from currency formatted string
                    var num = parseFloat(__currency_trans_from_en(b, false).replace(/[^\d.-]/g, '')) || 0;
                    return a + num;
                }, 0);
                var sell = api.column(9, {page: 'current'}).data().reduce(function(a, b) {
                    // Extract numeric value from currency formatted string
                    var num = parseFloat(__currency_trans_from_en(b, false).replace(/[^\d.-]/g, '')) || 0;
                    return a + num;
                }, 0);
                var compensation = api.column(10, {page: 'current'}).data().reduce(function(a, b) {
                    // Extract numeric value from currency formatted string
                    var num = parseFloat(__currency_trans_from_en(b, false).replace(/[^\d.-]/g, '')) || 0;
                    return a + num;
                }, 0);
                
                // Update footer cells
                $('#record_footer_quantity').html(__currency_trans_from_en(quantity, false, false, __quantity_precision, true));
                $('#record_footer_dispatched_qty').html(__currency_trans_from_en(dispatchedQty, false, false, __quantity_precision, true));
                $('#record_footer_unapprove_qty').html(__currency_trans_from_en(unapproveQty, false, false, __quantity_precision, true));
                $('#record_footer_purchase').html(__currency_trans_from_en(purchase, true));
                $('#record_footer_sell').html(__currency_trans_from_en(sell, true));
                $('#record_footer_compensation').html(__currency_trans_from_en(compensation, true));
            }
        });

        $('#damage_record_filter_location, #damage_record_filter_brand, #damage_record_filter_category, #damage_record_filter_status').on('change', function() {
            damage_record_table.ajax.reload();
        });

        $('#damage_record_filter_date_range').daterangepicker(dateRangeSettings, function(start, end) {
            $('#damage_record_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            $('#damage_record_filter_date_range').data('start_date', start.format('YYYY-MM-DD'));
            $('#damage_record_filter_date_range').data('end_date', end.format('YYYY-MM-DD'));
            damage_record_table.ajax.reload();
        });

        $(document).on('click', '.delete-damage-record', function() {
            var url = $(this).data('href');
            swal({
                title: LANG.sure,
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    $.ajax({
                        method: 'DELETE',
                        url: url,
                        dataType: 'json',
                        success: function(result) {
                            if (result.success) {
                                toastr.success(result.msg);
                                damage_record_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                }
            });
        });

        // View/Edit modal handler
        $(document).on('click', '.btn-modal', function(e) {
            e.preventDefault();
            var container = $(this).data('container') || '.view_modal';
            var url = $(this).data('href');

            if (url) {
                // Show loading state
                $(container).html('<div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-body text-center"><i class="fa fa-spinner fa-spin fa-3x"></i><br><br>@lang("messages.loading")...</div></div></div>').modal('show');

                $.get(url, function(data) {
                    $(container).html(data).modal('show');
                }).fail(function() {
                    toastr.error('Error loading content');
                    $(container).modal('hide');
                });
            }
        });

        // Handle status change
        $(document).on('change', '.change-dispatch-status', function() {
            var recordId = $(this).data('id');
            var newStatus = $(this).val();
            var selectElement = $(this);
            var originalValue = $(this).data('original-value') || '';

            $.ajax({
                url: '/posf/public/damage-records/' + recordId + '/update-status',
                method: 'POST',
                data: {
                    status: newStatus,
                    _token: '{{ csrf_token() }}'
                },
                beforeSend: function() {
                    selectElement.prop('disabled', true);
                },
                success: function(result) {
                    if (result.success) {
                        toastr.success(result.msg);
                        damage_record_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                        selectElement.val(originalValue);
                    }
                },
                error: function() {
                    toastr.error('@lang("messages.something_went_wrong")');
                    selectElement.val(originalValue);
                },
                complete: function() {
                    selectElement.prop('disabled', false);
                }
            });
        });

        // Handle approve button
        $(document).on('click', '.approve-damage-record', function() {
            var recordId = $(this).data('id');
            var button = $(this);
            
            swal({
                title: LANG.sure,
                icon: "info",
                text: "Are you sure you want to approve this damage record?",
                buttons: true,
            }).then((willApprove) => {
                if (willApprove) {
                    var approveUrl = '{{ action([\Modules\DamageManagement\Http\Controllers\DamageRecordController::class, "approveRecord"], ["record" => "__RECORD__"]) }}';
                    approveUrl = approveUrl.replace('__RECORD__', recordId);
                    $.ajax({
                        url: approveUrl,
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        beforeSend: function() {
                            button.prop('disabled', true);
                        },
                        success: function(result) {
                            if (result.success) {
                                toastr.success(result.msg);
                                damage_record_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        },
                        error: function() {
                            toastr.error('@lang("messages.something_went_wrong")');
                        },
                        complete: function() {
                            button.prop('disabled', false);
                        }
                    });
                }
            });
        });

        // Handle reject button
        $(document).on('click', '.reject-damage-record', function() {
            var recordId = $(this).data('id');
            var button = $(this);
            
            swal({
                title: LANG.sure,
                icon: "warning",
                text: "Are you sure you want to reject this damage record?",
                buttons: true,
                dangerMode: true,
            }).then((willReject) => {
                if (willReject) {
                    var rejectUrl = '{{ action([\Modules\DamageManagement\Http\Controllers\DamageRecordController::class, "rejectRecord"], ["record" => "__RECORD__"]) }}';
                    rejectUrl = rejectUrl.replace('__RECORD__', recordId);
                    $.ajax({
                        url: rejectUrl,
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        beforeSend: function() {
                            button.prop('disabled', true);
                        },
                        success: function(result) {
                            if (result.success) {
                                toastr.success(result.msg);
                                damage_record_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        },
                        error: function() {
                            toastr.error('@lang("messages.something_went_wrong")');
                        },
                        complete: function() {
                            button.prop('disabled', false);
                        }
                    });
                }
            });
        });

        // Handle approval status change from dropdown
        $(document).on('click', '.change-approval-status-item', function(e) {
            e.preventDefault();
            var recordId = $(this).data('id');
            var newStatus = $(this).data('status');
            var url = '{{ action([\Modules\DamageManagement\Http\Controllers\DamageRecordController::class, "updateApprovalStatus"], ["record" => "__RECORD__"]) }}';
            url = url.replace('__RECORD__', recordId);
            
            $.ajax({
                url: url,
                method: 'POST',
                data: {
                    status: newStatus,
                    _token: '{{ csrf_token() }}'
                },
                success: function(result) {
                    if (result.success) {
                        toastr.success(result.msg);
                        damage_record_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                },
                error: function(xhr) {
                    console.error('Error:', xhr.responseText);
                    toastr.error('@lang("messages.something_went_wrong")');
                }
            });
            
            return false;
        });
        
        // Handle dispatch status change from dropdown
        $(document).on('click', '.change-dispatch-status-item', function(e) {
            e.preventDefault();
            var recordId = $(this).data('id');
            var newStatus = $(this).data('status');
            
            // If changing to "partial", show modal for quantity entry
            if (newStatus === 'partial') {
                // Store recordId and status for later use
                $('#partial_dispatch_record_id').val(recordId);
                $('#partial_dispatch_modal').modal('show');
                return false;
            }
            
            var url = '{{ action([\Modules\DamageManagement\Http\Controllers\DamageRecordController::class, "updateStatus"], ["record" => "__RECORD__"]) }}';
            url = url.replace('__RECORD__', recordId);
            
            $.ajax({
                url: url,
                method: 'POST',
                data: {
                    status: newStatus,
                    _token: '{{ csrf_token() }}'
                },
                success: function(result) {
                    if (result.success) {
                        toastr.success(result.msg);
                        damage_record_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                },
                error: function(xhr) {
                    console.error('Error:', xhr.responseText);
                    toastr.error('@lang("messages.something_went_wrong")');
                }
            });
            
            return false;
        });
        
        // Handle partial dispatch submit
        $('#submit_partial_dispatch').on('click', function() {
            var recordId = $('#partial_dispatch_record_id').val();
            var dispatchQuantity = parseFloat($('#partial_dispatch_quantity').val());
            
            if (!dispatchQuantity || dispatchQuantity <= 0) {
                toastr.error('Please enter a valid dispatch quantity');
                return;
            }
            
            var url = '{{ action([\Modules\DamageManagement\Http\Controllers\DamageRecordController::class, "partialDispatch"], ["record" => "__RECORD__"]) }}';
            url = url.replace('__RECORD__', recordId);
            
            $.ajax({
                url: url,
                method: 'POST',
                data: {
                    dispatch_quantity: dispatchQuantity,
                    _token: '{{ csrf_token() }}'
                },
                success: function(result) {
                    if (result.success) {
                        toastr.success(result.msg);
                        $('#partial_dispatch_modal').modal('hide');
                        damage_record_table.ajax.reload();
                        $('#partial_dispatch_quantity').val('');
                        $('#partial_dispatch_record_id').val('');
                    } else {
                        toastr.error(result.msg);
                    }
                },
                error: function(xhr) {
                    console.error('Error:', xhr.responseText);
                    toastr.error('@lang("messages.something_went_wrong")');
                }
            });
        });
        
        // Load product details when modal opens for partial dispatch
        $('#partial_dispatch_modal').on('show.bs.modal', function() {
            // Get record details and show remaining quantity
            var recordId = $('#partial_dispatch_record_id').val();
            if (recordId) {
                // You can load and display remaining quantity info here
                $('#partial_dispatch_quantity').focus();
            }
        });
    });
    </script>
@endsection

<!-- Partial Dispatch Modal -->
<div class="modal fade" id="partial_dispatch_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">@lang('damagemanagement::damage.partial_dispatch')</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="partial_dispatch_record_id">
                <div class="form-group">
                    <label>@lang('damagemanagement::damage.enter_dispatch_quantity')</label>
                    <input type="number" class="form-control input_number" id="partial_dispatch_quantity" step="0.01" min="0.01" placeholder="0.00">
                    <small class="help-block">@lang('damagemanagement::damage.remaining_quantity_available')</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.cancel')</button>
                <button type="button" class="btn btn-primary" id="submit_partial_dispatch">@lang('messages.add')</button>
            </div>
        </div>
    </div>
</div>

@section('javascript')
    <script>
        // Script already added above
    </script>
@endsection
