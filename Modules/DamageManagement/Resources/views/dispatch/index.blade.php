@extends('layouts.app')

@section('title', __('damagemanagement::damage.dispatch_list'))

@section('content')
<section class="content-header">
    <h1>@lang('damagemanagement::damage.dispatches')</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        <div class="row">
            <div class="col-md-12">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover" id="damage_dispatch_table" width="100%">
                        <thead>
                            <tr>
                                <th>@lang('lang_v1.date')</th>
                                <th>@lang('lang_v1.reference_no')</th>
                                <th>@lang('business.business_location')</th>
                                <th>@lang('damagemanagement::damage.total_purchase_value')</th>
                                <th>@lang('damagemanagement::damage.total_sell_value')</th>
                                <th>@lang('damagemanagement::damage.total_compensation_value')</th>
                                <th>@lang('lang_v1.action')</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endcomponent
</section>

@endsection

@section('javascript')
<script>
    $(document).ready(function() {
        var damage_dispatch_table = $('#damage_dispatch_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '/damage-dispatches',
            buttons: [
                {
                    text: '<i class="fa fa-plus"></i> @lang('damagemanagement::damage.create_dispatch')',
                    action: function(e, dt, node, config) {
                        window.location.href = '{{action([\Modules\DamageManagement\Http\Controllers\DamageDispatchController::class, "create"])}}';
                    }
                }
            ],
            columns: [
                {data: 'dispatched_at', name: 'dispatched_at'},
                {data: 'reference_no', name: 'reference_no'},
                {data: 'location_name', name: 'location_name'},
                {data: 'total_purchase_value', name: 'total_purchase_value'},
                {data: 'total_sell_value', name: 'total_sell_value'},
                {data: 'total_compensation_value', name: 'total_compensation_value'},
                {data: 'action', name: 'action', orderable: false, searchable: false},
            ]
        });

        $(document).on('click', '.delete-damage-dispatch', function() {
            var url = $(this).data('href');
            swal({
                title: LANG.sure,
                text: "@lang('lang_v1.all_associated_data_will_be_deleted')",
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
                                damage_dispatch_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                }
            });
        });
    });
</script>
@endsection

