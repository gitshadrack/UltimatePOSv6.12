@extends('layouts.app')
@section('title', __('superadmin::lang.superadmin') . ' | ' . __('superadmin::lang.maintenance_mode'))

@section('content')
@include('superadmin::layouts.nav')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        @lang('superadmin::lang.maintenance_mode')
        <small>@lang('superadmin::lang.maintenance_mode_description')</small>
    </h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="tw-mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('maintenance_bypass_url'))
                <div class="alert alert-warning">
                    <h4><i class="fa fa-key"></i> @lang('superadmin::lang.maintenance_bypass_link')</h4>
                    <p>@lang('superadmin::lang.maintenance_bypass_link_help')</p>
                    <p class="tw-break-all">
                        <code>{{ session('maintenance_bypass_url') }}</code>
                    </p>
                </div>
            @endif

            @if ($is_down)
                @component('components.widget', [
                    'class' => 'box-danger',
                    'title' => __('superadmin::lang.maintenance_is_active')
                ])
                    <div class="alert alert-danger">
                        <h4>
                            <i class="fa fa-exclamation-triangle"></i>
                            @lang('superadmin::lang.system_offline_for_users')
                        </h4>
                        <p>@lang('superadmin::lang.maintenance_active_help')</p>
                        @if (! empty($maintenance_data['retry']))
                            <p>
                                <strong>@lang('superadmin::lang.retry_after'):</strong>
                                {{ $maintenance_data['retry'] }} @lang('superadmin::lang.seconds')
                            </p>
                        @endif
                    </div>

                    <div class="well">
                        <p>
                            <strong>@lang('superadmin::lang.emergency_recovery'):</strong>
                            @lang('superadmin::lang.emergency_recovery_help')
                        </p>
                        <code>php artisan up</code>
                    </div>

                    <form method="POST"
                        action="{{ route('superadmin.maintenance.disable') }}"
                        onsubmit="return confirm(@json(__('superadmin::lang.disable_maintenance_confirmation')));">
                        @csrf

                        <div class="form-group">
                            <label for="disable_admin_password">@lang('business.password'):*</label>
                            <input type="password"
                                name="admin_password"
                                id="disable_admin_password"
                                class="form-control"
                                autocomplete="current-password"
                                required>
                        </div>

                        <div class="form-group">
                            <label for="disable_confirm_text">
                                @lang('superadmin::lang.type_online_to_confirm'):*
                            </label>
                            <input type="text"
                                name="confirm_text"
                                id="disable_confirm_text"
                                class="form-control"
                                autocomplete="off"
                                required>
                        </div>

                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fa fa-power-off"></i>
                            @lang('superadmin::lang.return_system_online')
                        </button>
                    </form>
                @endcomponent
            @else
                @component('components.widget', [
                    'class' => 'box-warning',
                    'title' => __('superadmin::lang.maintenance_is_inactive')
                ])
                    <div class="alert alert-info">
                        <h4>
                            <i class="fa fa-info-circle"></i>
                            @lang('superadmin::lang.system_currently_online')
                        </h4>
                        <p>@lang('superadmin::lang.enable_maintenance_help')</p>
                    </div>

                    <div class="alert alert-warning">
                        <strong>@lang('superadmin::lang.before_enabling_maintenance')</strong>
                        <ul>
                            <li>@lang('superadmin::lang.maintenance_precaution_backup')</li>
                            <li>@lang('superadmin::lang.maintenance_precaution_users')</li>
                            <li>@lang('superadmin::lang.maintenance_precaution_transactions')</li>
                            <li>@lang('superadmin::lang.maintenance_precaution_cron')</li>
                        </ul>
                    </div>

                    <form method="POST"
                        action="{{ route('superadmin.maintenance.enable') }}"
                        onsubmit="return confirm(@json(__('superadmin::lang.enable_maintenance_confirmation')));">
                        @csrf

                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="backup_confirmed" value="1" required>
                                @lang('superadmin::lang.backup_confirmed')
                            </label>
                        </div>

                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="users_notified" value="1" required>
                                @lang('superadmin::lang.users_notified')
                            </label>
                        </div>

                        <div class="form-group">
                            <label for="retry_seconds">@lang('superadmin::lang.retry_after_seconds'):*</label>
                            <input type="number"
                                name="retry_seconds"
                                id="retry_seconds"
                                class="form-control"
                                value="{{ old('retry_seconds', 60) }}"
                                min="30"
                                max="3600"
                                required>
                            <p class="help-block">@lang('superadmin::lang.retry_after_seconds_help')</p>
                        </div>

                        <div class="form-group">
                            <label for="enable_admin_password">@lang('business.password'):*</label>
                            <input type="password"
                                name="admin_password"
                                id="enable_admin_password"
                                class="form-control"
                                autocomplete="current-password"
                                required>
                        </div>

                        <div class="form-group">
                            <label for="enable_confirm_text">
                                @lang('superadmin::lang.type_maintenance_to_confirm'):*
                            </label>
                            <input type="text"
                                name="confirm_text"
                                id="enable_confirm_text"
                                class="form-control"
                                value="{{ old('confirm_text') }}"
                                autocomplete="off"
                                required>
                        </div>

                        <button type="submit" class="btn btn-danger btn-lg">
                            <i class="fa fa-tools"></i>
                            @lang('superadmin::lang.enable_maintenance_mode')
                        </button>
                    </form>
                @endcomponent
            @endif
        </div>
    </div>
</section>
@stop
