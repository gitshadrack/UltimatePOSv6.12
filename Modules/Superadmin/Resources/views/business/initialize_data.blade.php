@extends('layouts.app')
@section('title', __('superadmin::lang.initialize_business_data'))

@section('content')
@include('superadmin::layouts.nav')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        @lang('superadmin::lang.initialize_business_data')
        <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">{{ $business->name }}</small>
    </h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            @component('components.widget', ['class' => 'box-danger', 'title' => __('superadmin::lang.initialize_business_data')])
                <div class="alert alert-danger">
                    <strong>@lang('superadmin::lang.important'):</strong>
                    @lang('superadmin::lang.initialize_business_data_warning')
                </div>

                {!! Form::open(['url' => action([\Modules\Superadmin\Http\Controllers\BusinessController::class, 'initializeData'], [$business->id]), 'method' => 'post', 'id' => 'initialize_business_data_form']) !!}
                    <div class="form-group">
                        {!! Form::label('business_name', __('business.business_name') . ':') !!}
                        {!! Form::text('business_name', $business->name, ['class' => 'form-control', 'readonly']) !!}
                    </div>

                    <div class="form-group">
                        {!! Form::label('location_id', __('business.business_location') . ':') !!}
                        {!! Form::select('location_id', $locations, null, ['class' => 'form-control select2', 'placeholder' => __('lang_v1.all')]) !!}
                        <p class="help-block">@lang('superadmin::lang.initialize_business_location_help')</p>
                    </div>

                    <div class="well">
                        <p><strong>@lang('superadmin::lang.data_that_will_be_deleted')</strong></p>
                        <ul>
                            <li>@lang('superadmin::lang.initialize_delete_transactions')</li>
                            <li>@lang('superadmin::lang.initialize_delete_payments')</li>
                            <li>@lang('superadmin::lang.initialize_delete_registers')</li>
                            <li>@lang('superadmin::lang.initialize_reset_stock')</li>
                        </ul>
                        <p><strong>@lang('superadmin::lang.data_that_will_remain')</strong></p>
                        <ul>
                            <li>@lang('superadmin::lang.initialize_keep_products')</li>
                            <li>@lang('superadmin::lang.initialize_keep_business_setup')</li>
                        </ul>
                    </div>

                    <div class="form-group">
                        {!! Form::label('admin_password', __('business.password') . ':*') !!}
                        {!! Form::password('admin_password', ['class' => 'form-control', 'required']) !!}
                    </div>

                    <div class="form-group">
                        {!! Form::label('confirm_text', __('superadmin::lang.type_reset_to_confirm') . ':*') !!}
                        {!! Form::text('confirm_text', null, ['class' => 'form-control', 'required', 'placeholder' => 'RESET']) !!}
                    </div>

                    <div class="text-right">
                        <a href="{{ action([\Modules\Superadmin\Http\Controllers\BusinessController::class, 'index']) }}" class="btn btn-default">
                            @lang('messages.cancel')
                        </a>
                        <button type="submit" class="btn btn-danger">
                            <i class="fa fa-refresh"></i> @lang('superadmin::lang.initialize_business_data')
                        </button>
                    </div>
                {!! Form::close() !!}
            @endcomponent
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        $('.select2').select2();

        $('form#initialize_business_data_form').on('submit', function(e) {
            if ($('#confirm_text').val() !== 'RESET') {
                e.preventDefault();
                toastr.error(@json(__('superadmin::lang.type_reset_to_confirm')));
                return false;
            }

            if (! confirm(@json(__('superadmin::lang.initialize_business_data_confirm')))) {
                e.preventDefault();
                return false;
            }
        });
    });
</script>
@endsection
