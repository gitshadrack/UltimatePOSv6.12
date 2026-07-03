@extends('layouts.app')
@section('title', __('lang_v1.daraja_settings'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('lang_v1.daraja_settings')</h1>
</section>
<section class="content">
    @if($migration_required)
        <div class="alert alert-warning">@lang('lang_v1.run_daraja_migration')</div>
    @endif
    {!! Form::open(['route' => 'daraja.settings.update', 'method' => 'post']) !!}
    <div class="box box-primary">
        <div class="box-body">
            <p class="help-block">@lang('lang_v1.daraja_settings_help')</p>
            @foreach($locations as $location)
                @php $setting = $settings->get($location->id); @endphp
                <div class="panel panel-default">
                    <div class="panel-heading"><strong>{{ $location->name }}</strong></div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-2">
                                {!! Form::label("locations[$location->id][environment]", __('lang_v1.environment')) !!}
                                {!! Form::select("locations[$location->id][environment]", ['sandbox' => __('lang_v1.sandbox'), 'production' => __('lang_v1.production')], optional($setting)->environment ?: 'sandbox', ['class' => 'form-control']) !!}
                            </div>
                            <div class="col-md-3">
                                {!! Form::label("locations[$location->id][consumer_key]", __('lang_v1.consumer_key')) !!}
                                {!! Form::password("locations[$location->id][consumer_key]", ['class' => 'form-control', 'placeholder' => !empty(optional($setting)->consumer_key) ? __('lang_v1.leave_blank_to_keep_current') : '']) !!}
                            </div>
                            <div class="col-md-3">
                                {!! Form::label("locations[$location->id][consumer_secret]", __('lang_v1.consumer_secret')) !!}
                                {!! Form::password("locations[$location->id][consumer_secret]", ['class' => 'form-control', 'placeholder' => !empty(optional($setting)->consumer_secret) ? __('lang_v1.leave_blank_to_keep_current') : '']) !!}
                            </div>
                            <div class="col-md-2">
                                {!! Form::label("locations[$location->id][business_shortcode]", __('lang_v1.business_shortcode')) !!}
                                {!! Form::text("locations[$location->id][business_shortcode]", optional($setting)->business_shortcode, ['class' => 'form-control']) !!}
                            </div>
                            <div class="col-md-2">
                                {!! Form::label("locations[$location->id][passkey]", __('lang_v1.daraja_passkey')) !!}
                                {!! Form::password("locations[$location->id][passkey]", ['class' => 'form-control', 'placeholder' => !empty(optional($setting)->passkey) ? __('lang_v1.leave_blank_to_keep_current') : '']) !!}
                            </div>
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-md-3">
                                {!! Form::label("locations[$location->id][transaction_type]", __('lang_v1.daraja_transaction_type')) !!}
                                {!! Form::select("locations[$location->id][transaction_type]", ['CustomerPayBillOnline' => __('lang_v1.paybill'), 'CustomerBuyGoodsOnline' => __('lang_v1.buy_goods')], optional($setting)->transaction_type ?: 'CustomerPayBillOnline', ['class' => 'form-control']) !!}
                            </div>
                            <div class="col-md-3">
                                {!! Form::label("locations[$location->id][account_reference]", __('lang_v1.account_reference')) !!}
                                {!! Form::text("locations[$location->id][account_reference]", optional($setting)->account_reference ?: 'UltimatePOS', ['class' => 'form-control', 'maxlength' => 12]) !!}
                            </div>
                            <div class="col-md-6">
                                {!! Form::label("locations[$location->id][callback_url]", __('lang_v1.stk_callback_url')) !!}
                                {!! Form::text("locations[$location->id][callback_url]", optional($setting)->callback_url, ['class' => 'form-control']) !!}
                                @if(!empty($setting))<p class="help-block"><code>{{ route('daraja.callback', ['setting' => $setting->id, 'token' => $setting->callback_token]) }}</code></p>@endif
                            </div>
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-md-5">
                                {!! Form::label("locations[$location->id][confirmation_url]", __('lang_v1.c2b_confirmation_url')) !!}
                                {!! Form::text("locations[$location->id][confirmation_url]", optional($setting)->confirmation_url, ['class' => 'form-control']) !!}
                                @if(!empty($setting))<p class="help-block"><code>{{ route('daraja.c2b_confirmation', ['setting' => $setting->id, 'token' => $setting->callback_token]) }}</code></p>@endif
                            </div>
                            <div class="col-md-5">
                                {!! Form::label("locations[$location->id][validation_url]", __('lang_v1.c2b_validation_url')) !!}
                                {!! Form::text("locations[$location->id][validation_url]", optional($setting)->validation_url, ['class' => 'form-control']) !!}
                                @if(!empty($setting))<p class="help-block"><code>{{ route('daraja.c2b_validation', ['setting' => $setting->id, 'token' => $setting->callback_token]) }}</code></p>@endif
                            </div>
                            <div class="col-md-2">
                                {!! Form::label("locations[$location->id][c2b_response_type]", __('lang_v1.c2b_response_type')) !!}
                                {!! Form::select("locations[$location->id][c2b_response_type]", ['Completed' => 'Completed', 'Cancelled' => 'Cancelled'], optional($setting)->c2b_response_type ?: 'Completed', ['class' => 'form-control']) !!}
                            </div>
                        </div>
                        <div class="checkbox"><label>{!! Form::checkbox("locations[$location->id][is_active]", 1, empty($setting) || !empty($setting->is_active), ['class' => 'input-icheck']) !!} @lang('business.is_active')</label></div>
                        @if(!empty($setting))
                            <button type="button" class="btn btn-default register-daraja-urls" data-url="{{ route('daraja.register_urls', $setting->id) }}"><i class="fa fa-link"></i> @lang('lang_v1.register_c2b_urls')</button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        <div class="box-footer">
            <button type="submit" class="btn btn-primary" @if($migration_required) disabled @endif>@lang('messages.save')</button>
            <a href="{{ route('daraja.transactions') }}" class="btn btn-default">@lang('lang_v1.daraja_transactions')</a>
        </div>
    </div>
    {!! Form::close() !!}
</section>
@endsection

@section('javascript')
<script>
$(document).on('click', '.register-daraja-urls', function () {
    var button = $(this);
    button.prop('disabled', true);
    $.post(button.data('url')).done(function (result) {
        toastr[result.success ? 'success' : 'error'](result.msg);
    }).fail(function () {
        toastr.error(LANG.something_went_wrong);
    }).always(function () {
        button.prop('disabled', false);
    });
});
</script>
@endsection
