@extends('layouts.app')

@section('title', __('lang_v1.intasend_integration'))

@section('content')

<section class="content-header">
    <h1>@lang('lang_v1.intasend_integration')</h1>
</section>

<section class="content">
    @if(!empty($migration_required))
        <div class="alert alert-warning">
            @lang('lang_v1.run_intasend_migration')
        </div>
    @endif

    {!! Form::open(['url' => action([\App\Http\Controllers\IntaSendController::class, 'updateSettings']), 'method' => 'post']) !!}
    <div class="box box-solid">
        <div class="box-header">
            <h3 class="box-title">@lang('lang_v1.intasend_location_settings')</h3>
        </div>
        <div class="box-body">
            @foreach($locations as $location)
                @php
                    $setting = $settings->get($location->id);
                @endphp
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>{{ $location->name }}</strong>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    {!! Form::label("locations[$location->id][till_or_paybill_number]", __('lang_v1.till_or_paybill_number') . ':') !!}
                                    {!! Form::text("locations[$location->id][till_or_paybill_number]", optional($setting)->till_or_paybill_number, ['class' => 'form-control', 'placeholder' => '4012345']) !!}
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    {!! Form::label("locations[$location->id][intasend_public_key]", __('lang_v1.intasend_public_key') . ':') !!}
                                    {!! Form::password("locations[$location->id][intasend_public_key]", ['class' => 'form-control', 'placeholder' => !empty(optional($setting)->intasend_public_key) ? '********' : '', 'title' => __('lang_v1.leave_blank_to_keep_current'), 'autocomplete' => 'new-password']) !!}
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    {!! Form::label("locations[$location->id][intasend_secret_key]", __('lang_v1.intasend_secret_key') . ':') !!}
                                    {!! Form::password("locations[$location->id][intasend_secret_key]", ['class' => 'form-control', 'placeholder' => !empty(optional($setting)->intasend_secret_key) ? '********' : '', 'title' => __('lang_v1.leave_blank_to_keep_current'), 'autocomplete' => 'new-password']) !!}
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    {!! Form::label("locations[$location->id][payment_link_url]", __('lang_v1.payment_link_url') . ':') !!}
                                    {!! Form::text("locations[$location->id][payment_link_url]", optional($setting)->payment_link_url, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    {!! Form::label("locations[$location->id][webhook_secret]", __('lang_v1.webhook_secret') . ':') !!}
                                    {!! Form::password("locations[$location->id][webhook_secret]", ['class' => 'form-control', 'placeholder' => !empty(optional($setting)->webhook_secret) ? '********' : '', 'title' => __('lang_v1.leave_blank_to_keep_current'), 'autocomplete' => 'new-password']) !!}
                                    <p class="help-block">@lang('lang_v1.intasend_webhook_secret_help')</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="checkbox" style="margin-top: 28px;">
                                    <label>
                                        {!! Form::checkbox("locations[$location->id][require_webhook_signature]", 1, !empty($setting->require_webhook_signature), ['class' => 'input-icheck']) !!}
                                        @lang('lang_v1.require_webhook_signature')
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox("locations[$location->id][is_active]", 1, empty($setting) || !empty($setting->is_active), ['class' => 'input-icheck']) !!}
                                @lang('business.is_active')
                            </label>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="box-footer">
            <button type="submit" class="btn btn-primary" @if(!empty($migration_required)) disabled @endif>@lang('messages.save')</button>
            <a href="{{ action([\App\Http\Controllers\IntaSendController::class, 'pool']) }}" class="btn btn-default">
                @lang('lang_v1.intasend_holding_pool')
            </a>
            <a href="{{ action([\App\Http\Controllers\IntaSendController::class, 'collections']) }}" class="btn btn-default">
                @lang('lang_v1.intasend_collections_report')
            </a>
        </div>
    </div>
    {!! Form::close() !!}
</section>

@endsection
