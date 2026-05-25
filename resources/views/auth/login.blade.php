@extends('layouts.auth2')
@section('title', __('lang_v1.login'))
@section('body_class', 'login-split-page')
@inject('request', 'Illuminate\Http\Request')
@section('content')
    @php
        $username = old('username');
        $password = null;
        if (config('app.env') == 'demo') {
            $username = 'admin';
            $password = '123456';

            $demo_types = [
                'all_in_one' => 'admin',
                'super_market' => 'admin',
                'pharmacy' => 'admin-pharmacy',
                'electronics' => 'admin-electronics',
                'services' => 'admin-services',
                'restaurant' => 'admin-restaurant',
                'superadmin' => 'superadmin',
                'woocommerce' => 'woocommerce_user',
                'essentials' => 'admin-essentials',
                'manufacturing' => 'manufacturer-demo',
            ];

            if (!empty($_GET['demo_type']) && array_key_exists($_GET['demo_type'], $demo_types)) {
                $username = $demo_types[$_GET['demo_type']];
            }
        }

        $is_numeric_login_enabled = false;
        $login_locations = collect();
        $selected_location_id = old('location_id');
        $login_type = old('login_type') == 'password' ? 'password' : 'pin';
        $login_business_name = null;
        $login_business_id = null;
        $login_image_url = asset('img/login-side.jpg');
        $normalize_login_key = function ($value) {
            $value = trim((string) $value);
            if ($value === '') {
                return null;
            }

            $parseable_value = preg_match('/^[a-z][a-z0-9+\-.]*:\/\//i', $value)
                ? $value
                : 'http://' . ltrim($value, '/');
            $value_host = parse_url($parseable_value, PHP_URL_HOST);
            $value = !empty($value_host) ? $value_host : explode('/', $value)[0];
            $value = preg_replace('/:\d+$/', '', $value);

            return strtolower(preg_replace('/^www\./', '', trim($value, " \t\n\r\0\x0B./")));
        };
        $get_login_alias = function ($value) {
            $value_parts = explode('.', $value);

            return count($value_parts) > 2 ? $value_parts[0] : null;
        };
        $login_key_matches = function ($configured_key, $request_key) use ($normalize_login_key, $get_login_alias) {
            $configured_key = $normalize_login_key($configured_key);
            if (empty($configured_key) || empty($request_key)) {
                return false;
            }

            if ($configured_key === $request_key) {
                return true;
            }

            return strpos($configured_key, '.') === false &&
                $configured_key === $get_login_alias($request_key);
        };
        try {
            if (
                \Illuminate\Support\Facades\Schema::hasColumn('business', 'tenant_domain') &&
                \Illuminate\Support\Facades\Schema::hasColumn('business', 'login_image')
            ) {
                $normalize_domain = function ($domain) {
                    $domain = trim((string) $domain);
                    if ($domain === '') {
                        return null;
                    }

                    $parseable_domain = preg_match('/^[a-z][a-z0-9+\-.]*:\/\//i', $domain)
                        ? $domain
                        : 'http://' . ltrim($domain, '/');
                    $domain_host = parse_url($parseable_domain, PHP_URL_HOST);
                    $domain = !empty($domain_host) ? $domain_host : explode('/', $domain)[0];
                    $domain = preg_replace('/:\d+$/', '', $domain);

                    return strtolower(preg_replace('/^www\./', '', trim($domain, " \t\n\r\0\x0B./")));
                };

                $get_subdomain_alias = function ($domain) {
                    $domain_parts = explode('.', $domain);

                    return count($domain_parts) > 2 ? $domain_parts[0] : null;
                };

                $tenant_domain_matches = function ($tenant_domain, $request_domain) use ($normalize_domain, $get_subdomain_alias) {
                    $tenant_domain = $normalize_domain($tenant_domain);
                    if (empty($tenant_domain) || empty($request_domain)) {
                        return false;
                    }

                    if ($tenant_domain === $request_domain) {
                        return true;
                    }

                    return strpos($tenant_domain, '.') === false &&
                        $tenant_domain === $get_subdomain_alias($request_domain);
                };

                $resolve_login_image_url = function ($login_image) {
                    $login_image = trim((string) $login_image);
                    if ($login_image === '') {
                        return null;
                    }

                    if (filter_var($login_image, FILTER_VALIDATE_URL)) {
                        return $login_image;
                    }

                    $login_image = str_replace('\\', '/', ltrim($login_image, '/'));
                    $login_image_filename = basename(parse_url($login_image, PHP_URL_PATH) ?: $login_image);
                    if ($login_image_filename === '') {
                        return null;
                    }

                    $image_candidates = [
                        $login_image,
                        'uploads/business_login_images/' . $login_image_filename,
                        'storage/business_login_images/' . $login_image_filename,
                    ];

                    foreach (array_unique($image_candidates) as $image_candidate) {
                        if (file_exists(public_path($image_candidate))) {
                            return route('tenant-login-image', ['filename' => $login_image_filename]);
                        }
                    }

                    $storage_image_candidates = [
                        storage_path('app/public/business_login_images/' . $login_image_filename),
                        storage_path('app/business_login_images/' . $login_image_filename),
                    ];

                    foreach ($storage_image_candidates as $storage_image_candidate) {
                        if (file_exists($storage_image_candidate)) {
                            return route('tenant-login-image', ['filename' => $login_image_filename]);
                        }
                    }

                    return null;
                };

                $normalized_host = $normalize_domain($request->getHost());
                $businesses = \App\Business::whereNotNull('tenant_domain')
                    ->select('id', 'name', 'tenant_domain', 'login_image')
                    ->get();

                foreach ($businesses as $business) {
                    if ($tenant_domain_matches($business->tenant_domain, $normalized_host)) {
                        $login_business_name = $business->name;
                        $login_business_id = $business->id;
                        $business_login_image_url = $resolve_login_image_url($business->login_image);
                        if (!empty($business_login_image_url)) {
                            $login_image_url = $business_login_image_url;
                        }
                        break;
                    }
                }
            }
        } catch (\Exception $e) {
            $login_business_name = null;
        }

        try {
            if (\Illuminate\Support\Facades\Schema::hasColumn('business_locations', 'enable_numeric_login')) {
                $has_location_login_domain = \Illuminate\Support\Facades\Schema::hasColumn('business_locations', 'login_domain');
                $location_select = [
                    'business_locations.id',
                    'business_locations.name',
                    'business_locations.location_id',
                    'business_locations.enable_numeric_login',
                    'business.name as business_name',
                ];

                if ($has_location_login_domain) {
                    $location_select[] = 'business_locations.login_domain';
                }

                $login_locations = \App\BusinessLocation::join('business', 'business.id', '=', 'business_locations.business_id')
                    ->where('business.is_active', 1)
                    ->where('business_locations.is_active', 1)
                    ->when(!empty($login_business_id), function ($query) use ($login_business_id) {
                        $query->where('business_locations.business_id', $login_business_id);
                    })
                    ->select($location_select)
                    ->orderBy('business.name')
                    ->orderBy('business_locations.name')
                    ->get();

                $preselected_login_location = null;
                $requested_login_location = trim((string) request()->query('location', request()->query('location_id', '')));

                if ($requested_login_location !== '') {
                    $requested_login_location_key = $normalize_login_key($requested_login_location);
                    $preselected_login_location = $login_locations->first(function ($location) use ($requested_login_location, $requested_login_location_key, $has_location_login_domain, $login_key_matches) {
                        if ((string) $location->id === $requested_login_location || (string) $location->location_id === $requested_login_location) {
                            return true;
                        }

                        return $has_location_login_domain &&
                            $login_key_matches($location->login_domain, $requested_login_location_key);
                    });
                }

                if (empty($preselected_login_location) && $has_location_login_domain) {
                    $normalized_host = $normalize_login_key($request->getHost());
                    $preselected_login_location = $login_locations->first(function ($location) use ($normalized_host, $login_key_matches) {
                        return $login_key_matches($location->login_domain, $normalized_host);
                    });
                }

                if (!empty($preselected_login_location)) {
                    $selected_location_id = $preselected_login_location->id;
                    $login_locations = collect([$preselected_login_location]);
                }

                $is_numeric_login_enabled = $login_locations->where('enable_numeric_login', 1)->isNotEmpty();
            }
        } catch (\Exception $e) {
            $is_numeric_login_enabled = false;
            $login_locations = collect();
        }

        if ($login_locations->isNotEmpty() && empty($selected_location_id)) {
            $selected_location_id = $login_locations->first()->id;
        }

        $selected_login_location = $login_locations->firstWhere('id', (int) $selected_location_id);
        if (!$is_numeric_login_enabled || empty($selected_login_location) || empty($selected_login_location->enable_numeric_login)) {
            $login_type = 'password';
        }
    @endphp
    <style>
        .login-split-page html,
        .login-split-page {
            min-height: 100%;
            overflow-x: hidden;
        }

        .login-split-page .container-fluid,
        .login-split-page .eq-height-row,
        .login-split-page .right-col {
            min-height: 100vh;
        }

        .login-split-page .container-fluid {
            padding: 0;
        }

        .login-split-page .right-col {
            padding: 0 !important;
            background: linear-gradient(to right, #6366f1, #3b82f6);
        }

        .auth-split-shell {
            min-height: 100vh;
            margin: 0;
        }

        .auth-image-panel {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #fff;
            padding: 0;
            background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 48%, #4338ca 100%);
        }

        .auth-floating-image-card {
            position: relative;
            width: 100%;
            min-height: 100vh;
            border-radius: 0;
            overflow: hidden;
            box-shadow: none;
            transform: none;
            isolation: isolate;
        }

        .auth-floating-image-card::before {
            content: "";
            position: absolute;
            inset: 0;
            z-index: 1;
            background: linear-gradient(180deg, rgba(0, 0, 0, 0.12), rgba(0, 0, 0, 0.58));
        }

        .auth-floating-image-card::after {
            display: none;
        }

        .auth-floating-image-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .auth-image-caption {
            position: absolute;
            left: 30px;
            right: 30px;
            bottom: 42px;
            z-index: 3;
        }

        .auth-image-title {
            font-size: 30px;
            font-weight: 800;
            line-height: 1.2;
            text-shadow: 0 2px 12px rgba(0, 0, 0, 0.35);
        }

        .auth-image-subtitle {
            margin-top: 12px;
            font-size: 24px;
            font-weight: 800;
            color: #2f83ff;
            letter-spacing: 0.03em;
        }

        .auth-form-panel {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 90px 24px 40px;
            background: linear-gradient(to right, #6366f1, #3b82f6);
        }

        .auth-form-panel .login-card-wrap {
            width: 100%;
            max-width: 560px;
        }

        .login-card {
            width: 100%;
        }

        .login-card-inner {
            max-width: none;
            padding: 34px;
        }

        .pin-keypad {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-top: 10px;
        }

        .pin-keypad-button {
            height: 66px;
            border: 0;
            border-radius: 4px;
            background: #1f73e0;
            color: #fff;
            font-size: 18px;
            font-weight: 700;
            line-height: 1;
            transition: background-color 0.15s ease, border-color 0.15s ease;
        }

        .pin-keypad-button:hover,
        .pin-keypad-button:focus {
            background: #155fc0;
            outline: none;
        }

        .pin-keypad-button:active {
            background: #104d9b;
        }

        .pin-keypad-action {
            font-size: 13px;
            font-weight: 700;
        }

        .access-code-card {
            max-width: 360px;
            margin: 0 auto;
            padding: 22px 20px 20px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.84);
        }

        .access-code-title {
            color: #ff7a1a;
            font-size: 17px;
            font-weight: 800;
            text-align: center;
            margin-bottom: 4px;
        }

        .access-code-input,
        .access-code-location {
            width: 100%;
            height: 34px;
            border: 1px solid #1f73e0;
            border-radius: 8px;
            background: #fff;
            color: #111827;
            font-size: 18px;
            text-align: center;
            outline: none;
        }

        .access-code-location {
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
            text-align: left;
        }

        .pin-keypad-login {
            background: #35c98d;
        }

        .pin-keypad-login:hover,
        .pin-keypad-login:focus {
            background: #24ad75;
        }

        .pin-keypad-clear {
            background: #ff7a1a;
        }

        .pin-keypad-clear:hover,
        .pin-keypad-clear:focus {
            background: #e76508;
        }

        .abc-login-toggle {
            width: 100%;
            height: 42px;
            margin-top: 10px;
            border: 0;
            border-radius: 6px;
            background: #2d4054;
            color: #fff;
            font-weight: 800;
        }

        .login-split-page .tw-absolute.tw-top-2,
        .login-split-page .tw-absolute.tw-top-5 {
            z-index: 5;
        }

        @media (max-width: 991px) {
            .auth-form-panel {
                padding-top: 110px;
            }

            .auth-form-panel .login-card-wrap {
                max-width: 520px;
            }
        }

        @media (max-width: 575px) {
            .auth-form-panel {
                padding: 96px 14px 28px;
            }

            .login-card-inner {
                padding: 22px;
            }
        }

        @media (min-width: 992px) {
            .login-split-page {
                height: 100vh;
                overflow: hidden;
            }

            .login-split-page .container-fluid,
            .login-split-page .eq-height-row,
            .login-split-page .right-col,
            .auth-split-shell,
            .auth-image-panel,
            .auth-form-panel,
            .auth-floating-image-card {
                height: 100vh;
                min-height: 100vh;
            }
        }
    </style>
    <div class="row auth-split-shell">
        <div class="col-md-5 hidden-sm hidden-xs auth-image-panel">
            <div class="auth-floating-image-card">
                <img src="{{ $login_image_url }}" alt="Login visual">
                @if(!empty($login_business_name))
                <div class="auth-image-caption">
                    <div class="auth-image-title">{{ $login_business_name }}</div>
                </div>
                @endif
            </div>
        </div>
        <div class="col-md-7 col-sm-12 col-xs-12 auth-form-panel">
            <div class="login-card-wrap">
                @if (config('app.env') == 'demo')
        
                @component('components.widget', [
                    'class' => 'box-primary',
                    'header' =>
                        '<h4 class="text-center">Demo Shops <small><i> <br/>Demos are for example purpose only, this application <u>can be used in many other similar businesses.</u></i> <br/><b>Click button to login that business</b></small></h4>',
                ])
                    <a href="?demo_type=all_in_one" class="btn btn-app bg-olive demo-login" data-toggle="tooltip"
                        title="Showcases all feature available in the application."
                        data-admin="{{ $demo_types['all_in_one'] }}"> <i class="fas fa-star"></i> All In One</a>

                    <a href="?demo_type=pharmacy" class="btn bg-maroon btn-app demo-login" data-toggle="tooltip"
                        title="Shops with products having expiry dates." data-admin="{{ $demo_types['pharmacy'] }}"><i
                            class="fas fa-medkit"></i>Pharmacy</a>

                    <a href="?demo_type=services" class="btn bg-orange btn-app demo-login" data-toggle="tooltip"
                        title="For all service providers like Web Development, Restaurants, Repairing, Plumber, Salons, Beauty Parlors etc."
                        data-admin="{{ $demo_types['services'] }}"><i class="fas fa-wrench"></i>Multi-Service Center</a>

                    <a href="?demo_type=electronics" class="btn bg-purple btn-app demo-login" data-toggle="tooltip"
                        title="Products having IMEI or Serial number code." data-admin="{{ $demo_types['electronics'] }}"><i
                            class="fas fa-laptop"></i>Electronics & Mobile Shop</a>

                    <a href="?demo_type=super_market" class="btn bg-navy btn-app demo-login" data-toggle="tooltip"
                        title="Super market & Similar kind of shops." data-admin="{{ $demo_types['super_market'] }}"><i
                            class="fas fa-shopping-cart"></i> Super Market</a>

                    <a href="?demo_type=restaurant" class="btn bg-red btn-app demo-login" data-toggle="tooltip"
                        title="Restaurants, Salons and other similar kind of shops."
                        data-admin="{{ $demo_types['restaurant'] }}"><i class="fas fa-utensils"></i> Restaurant</a>
                    <hr>

                    <i class="icon fas fa-plug"></i> Premium optional modules:<br><br>

                    <a href="?demo_type=superadmin" class="btn bg-red-active btn-app demo-login" data-toggle="tooltip"
                        title="SaaS & Superadmin extension Demo" data-admin="{{ $demo_types['superadmin'] }}"><i
                            class="fas fa-university"></i> SaaS / Superadmin</a>

                    <a href="?demo_type=woocommerce" class="btn bg-woocommerce btn-app demo-login" data-toggle="tooltip"
                        title="WooCommerce demo user - Open web shop in minutes!!" style="color:white !important"
                        data-admin="{{ $demo_types['woocommerce'] }}"> <i class="fab fa-wordpress"></i> WooCommerce</a>

                    <a href="?demo_type=essentials" class="btn bg-navy btn-app demo-login" data-toggle="tooltip"
                        title="Essentials & HRM (human resource management) Module Demo" style="color:white !important"
                        data-admin="{{ $demo_types['essentials'] }}">
                        <i class="fas fa-check-circle"></i>
                        Essentials & HRM</a>

                    <a href="?demo_type=manufacturing" class="btn bg-orange btn-app demo-login" data-toggle="tooltip"
                        title="Manufacturing module demo" style="color:white !important"
                        data-admin="{{ $demo_types['manufacturing'] }}">
                        <i class="fas fa-industry"></i>
                        Manufacturing Module</a>

                    <a href="?demo_type=superadmin" class="btn bg-maroon btn-app demo-login" data-toggle="tooltip"
                        title="Project module demo" style="color:white !important"
                        data-admin="{{ $demo_types['superadmin'] }}">
                        <i class="fas fa-project-diagram"></i>
                        Project Module</a>

                    <a href="?demo_type=services" class="btn btn-app demo-login" data-toggle="tooltip"
                        title="Advance repair module demo" style="color:white !important; background-color: #bc8f8f"
                        data-admin="{{ $demo_types['services'] }}">
                        <i class="fas fa-wrench"></i>
                        Advance Repair Module</a>

                    <a href="{{ url('docs') }}" target="_blank" class="btn btn-app" data-toggle="tooltip"
                        title="Advance repair module demo" style="color:white !important; background-color: #2dce89">
                        <i class="fas fa-network-wired"></i>
                        Connector Module / API Documentation</a>
                @endcomponent
            
            
        
                @endif
            <div
                class="login-card tw-p-5 md:tw-p-6 tw-mb-4 tw-rounded-2xl tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm tw-ring-1 tw-ring-gray-200">
                <div class="login-card-inner tw-flex tw-flex-col tw-gap-4 tw-dw-rounded-box">
                    <div class="password-login-field {{ $login_type == 'pin' ? 'hide' : '' }} tw-flex tw-items-center tw-flex-col">
                        <h1 class="tw-text-lg md:tw-text-xl tw-font-semibold tw-text-[#1e1e1e]">
                            @lang('lang_v1.welcome_back')
                        </h1>
                        <h2 class="tw-text-sm tw-font-medium tw-text-gray-500">
                            @lang('lang_v1.login_to_your') {{ config('app.name', 'ultimatePOS') }}
                        </h2>
                    </div>

                    <form method="POST" action="{{ route('login') }}" id="login-form">
                        {{ csrf_field() }}
                        <input type="hidden" name="login_type" id="login_type" value="{{ $login_type }}">
                        @if($login_locations->isNotEmpty())
                            <div class="form-group login-location-field">
                                @if($login_locations->count() == 1)
                                    @php $only_login_location = $login_locations->first(); @endphp
                                    <input type="hidden" name="location_id" id="location_id"
                                        value="{{ $selected_location_id }}"
                                        data-numeric-login="{{ !empty($only_login_location->enable_numeric_login) ? 1 : 0 }}">
                                @else
                                    <select name="location_id" id="location_id"
                                        class="access-code-location">
                                        <option value="">@lang('lang_v1.select_location')</option>
                                        @foreach($login_locations as $location)
                                            <option value="{{ $location->id }}"
                                                data-numeric-login="{{ !empty($location->enable_numeric_login) ? 1 : 0 }}"
                                                {{ (string) $selected_location_id === (string) $location->id ? 'selected' : '' }}>
                                                {{ $location->business_name }} - {{ $location->name }} @if(!empty($location->location_id))({{ $location->location_id }})@endif
                                            </option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>
                        @endif
                        @if($is_numeric_login_enabled)
                            <div class="access-code-card pin-login-field {{ $login_type == 'pin' ? '' : 'hide' }}">
                                <div class="access-code-title">@lang('lang_v1.enter_access_code')</div>

                                <input class="access-code-input" id="pin" type="password" inputmode="numeric"
                                    pattern="[0-9]*" maxlength="6" name="pin"
                                    {{ $login_type == 'pin' ? 'required' : '' }} autocomplete="off" />

                                <div class="pin-keypad" aria-label="@lang('lang_v1.numeric_keypad')">
                                    @foreach([1, 2, 3, 4, 5, 6, 7, 8, 9] as $key)
                                        <button type="button" class="pin-keypad-button" data-pin-key="{{ $key }}">{{ $key }}</button>
                                    @endforeach
                                    <button type="submit" class="pin-keypad-button pin-keypad-login">
                                        @lang('lang_v1.login')
                                    </button>
                                    <button type="button" class="pin-keypad-button" data-pin-key="0">0</button>
                                    <button type="button" class="pin-keypad-button pin-keypad-clear" data-pin-action="clear">
                                        @lang('lang_v1.clear')
                                    </button>
                                </div>
                                <button type="button" class="abc-login-toggle login-type-btn" data-login-type="password">
                                    @lang('lang_v1.abc_letters')
                                </button>
                                @if ($errors->has('pin'))
                                    <span class="help-block text-center">
                                        <strong>{{ $errors->first('pin') }}</strong>
                                    </span>
                                @endif
                                @if ($errors->has('location_id'))
                                    <span class="help-block text-center">
                                        <strong>{{ $errors->first('location_id') }}</strong>
                                    </span>
                                @endif
                            </div>
                        @endif
                        <div class="form-group has-feedback password-login-field {{ $login_type == 'pin' ? 'hide' : '' }} {{ $errors->has('username') ? ' has-error' : '' }}">
                            <label class="tw-dw-form-control">
                                <div class="tw-dw-label">
                                    <span
                                        class="tw-text-xs md:tw-text-sm tw-font-medium tw-text-black">@lang('lang_v1.username')</span>
                                </div>

                                <input
                                    class="tw-border tw-border-[#D1D5DA] tw-outline-none tw-h-12 tw-bg-transparent tw-rounded-lg tw-px-3 tw-font-medium tw-text-black placeholder:tw-text-gray-500 placeholder:tw-font-medium"
                                    name="username" {{ $login_type == 'pin' ? '' : 'required' }} autofocus placeholder="@lang('lang_v1.username')"
                                    data-last-active-input="" id="username" type="text" name="username"
                                    value="{{ $username }}" />
                                @if ($errors->has('username'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('username') }}</strong>
                                    </span>
                                @endif
                            </label>
                        </div>

                        <div class="form-group has-feedback password-login-field {{ $login_type == 'pin' ? 'hide' : '' }} {{ $errors->has('password') ? ' has-error' : '' }}">
                            <label class="tw-dw-form-control">
                                <div class="tw-dw-label">
                                    <span
                                        class="tw-text-xs md:tw-text-sm tw-font-medium tw-text-black">@lang('lang_v1.password')</span>
                                    @if (config('app.env') != 'demo')
                                        <a href="{{ route('password.request') }}"
                                            class="tw-text-xs md:tw-text-sm tw-font-medium tw-bg-gradient-to-r tw-from-indigo-500 tw-to-blue-500 tw-inline-block tw-text-transparent tw-bg-clip-text hover:tw-text-[#467BF5]"
                                            tabindex="-1">@lang('lang_v1.forgot_your_password')</a>
                                    @endif
                                </div>

                                <input
                                    class="tw-border tw-border-[#D1D5DA] tw-outline-none tw-h-12 tw-bg-transparent tw-rounded-lg tw-px-3 tw-font-medium tw-text-black placeholder:tw-text-gray-500 placeholder:tw-font-medium"
                                    id="password" type="password" name="password" value="{{ $password }}" {{ $login_type == 'pin' ? '' : 'required' }}
                                    placeholder="@lang('lang_v1.password')" />
                                <button type="button" id="show_hide_icon" class="show_hide_icon"
                                    style="position: absolute; top:48px;right:5px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-eye tw-w-6" viewBox="0 0 24 24" stroke-width="1.5" stroke="#000000" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                                        <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" />
                                    </svg>
                                </button>
                            </label>
                            @if ($errors->has('password'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('password') }}</strong>
                                </span>
                            @endif
                        </div>


                        <div class="tw-dw-form-control password-login-field {{ $login_type == 'pin' ? 'hide' : '' }}">
                            <label class="tw-dw-cursor-pointer tw-dw-label tw-self-start tw-gap-2">
                                <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}
                                    class="tw-dw-checkbox">
                                <span
                                    class="tw-text-xs md:tw-text-sm tw-font-medium tw-text-black tw-mt-[0.2rem]">@lang('lang_v1.remember_me')</span>
                            </label>
                        </div>
                        @if(config('constants.enable_recaptcha'))
                        <div class="row password-login-field {{ $login_type == 'pin' ? 'hide' : '' }}">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <div class="g-recaptcha" data-sitekey="{{ config('constants.google_recaptcha_key') }}"></div>
                                        @if ($errors->has('g-recaptcha-response'))
                                            <span class="text-danger">{{ $errors->first('g-recaptcha-response') }}</span>
                                        @endif
                                </div>  
                            </div>
                        </div>
                        @endif
                        <button type="submit"
                            class="password-login-field {{ $login_type == 'pin' ? 'hide' : '' }} tw-bg-gradient-to-r tw-from-indigo-500 tw-to-blue-500 tw-h-12 tw-rounded-xl tw-text-sm md:tw-text-base tw-text-white tw-font-semibold tw-w-full tw-max-w-full mt-2 hover:tw-from-indigo-600 hover:tw-to-blue-600 focus:tw-outline-none focus:tw-ring-2 tw-ring-blue-500 focus:tw-ring-offset-2 active:tw-from-indigo-700 active:tw-to-blue-700">
                            @lang('lang_v1.login')
                        </button>
                        @if($is_numeric_login_enabled)
                        <button type="button"
                            class="password-login-field login-type-btn {{ $login_type == 'pin' ? 'hide' : '' }} tw-h-12 tw-rounded-xl tw-text-sm md:tw-text-base tw-text-blue-700 tw-font-semibold tw-w-full tw-max-w-full mt-2 tw-border tw-border-blue-200 tw-bg-blue-50"
                            data-login-type="pin">
                            @lang('lang_v1.login_with_pin')
                        </button>
                        @endif
                    </form>

                    <div class="password-login-field {{ $login_type == 'pin' ? 'hide' : '' }} tw-flex tw-items-center tw-flex-col">
                        <!-- Register Url -->

                        @if (!($request->segment(1) == 'business' && $request->segment(2) == 'register'))
                            <!-- Register Url -->
                            @if (config('constants.allow_registration'))
                                <a href="{{ route('business.getRegister') }}@if (!empty(request()->lang)) {{ '?lang=' . request()->lang }} @endif"
                                    class="tw-text-sm tw-font-medium tw-text-gray-500 hover:tw-text-gray-500 tw-mt-2">{{ __('business.not_yet_registered') }}
                                    <span
                                        class="tw-text-sm tw-font-medium tw-bg-gradient-to-r tw-from-indigo-500 tw-to-blue-500 tw-inline-block tw-text-transparent tw-bg-clip-text hover:tw-text-[#467BF5] hover:tw-underline">{{ __('business.register_now') }}</span></a>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>

@stop
@section('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            $('#show_hide_icon').off('click');
            function selectedLocationUsesPin() {
                const locationInput = $('#location_id');

                if (!locationInput.length) {
                    return false;
                }

                if (locationInput.is('select')) {
                    return locationInput.find('option:selected').data('numeric-login') == 1;
                }

                return locationInput.data('numeric-login') == 1;
            }

            function setLoginType(type, forceLocationPreference) {
                if (forceLocationPreference) {
                    type = selectedLocationUsesPin() ? 'pin' : 'password';
                } else if (type === 'pin' && !selectedLocationUsesPin()) {
                    type = 'password';
                }

                $('#login_type').val(type);
                $('.login-type-btn[data-login-type="pin"]').toggleClass('hide', !selectedLocationUsesPin());

                if (type === 'pin') {
                    $('.password-login-field').addClass('hide');
                    $('.pin-login-field').removeClass('hide');
                    $('#username').prop('required', false);
                    $('#password').prop('required', false);
                    $('#pin').prop('required', true).focus();
                    $('#location_id').prop('required', true);
                } else {
                    $('.pin-login-field').addClass('hide');
                    $('.password-login-field').removeClass('hide');
                    $('#username').prop('required', true);
                    $('#pin').prop('required', false).val('');
                    $('#location_id').prop('required', $('#location_id').is('select'));
                    $('#password').prop('required', true).focus();
                }
            }

            $('#location_id').on('change', function() {
                setLoginType($('#login_type').val(), true);
            });

            setLoginType($('#login_type').val());

            $('.login-type-btn').on('click', function() {
                setLoginType($(this).data('login-type'));
            });

            $('.pin-keypad-button').off('click.pinKeypad').on('click.pinKeypad', function(e) {
                if ($(this).attr('type') === 'submit') {
                    return;
                }

                e.preventDefault();
                const pinInput = $('#pin');
                const key = $(this).attr('data-pin-key');
                const action = $(this).attr('data-pin-action');
                let currentPin = pinInput.val();

                if (action === 'clear') {
                    pinInput.val('').focus();
                    return;
                }

                if (action === 'backspace') {
                    pinInput.val(currentPin.slice(0, -1)).focus();
                    return;
                }

                if (typeof key !== 'undefined' && currentPin.length < 6) {
                    pinInput.val(currentPin + key).focus();
                }
            });

            $('.change_lang').click(function() {
                window.location = "{{ route('login') }}?lang=" + $(this).attr('value');
            });
            $('a.demo-login').click(function(e) {
                e.preventDefault();
                setLoginType('password');
                $('#username').val($(this).data('admin'));
                $('#password').val("{{ $password }}");
                $('form#login-form').submit();
            });

            $('#show_hide_icon').on('click', function(e) {
            e.preventDefault();
            const passwordInput = $('#password');

            if (passwordInput.attr('type') === 'password') {
                passwordInput.attr('type', 'text');
                $('#show_hide_icon').html('<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-eye-off tw-w-6" viewBox="0 0 24 24" stroke-width="1.5" stroke="#000000" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.585 10.587a2 2 0 0 0 2.829 2.828"/><path d="M16.681 16.673a8.717 8.717 0 0 1 -4.681 1.327c-3.6 0 -6.6 -2 -9 -6c1.272 -2.12 2.712 -3.678 4.32 -4.674m2.86 -1.146a9.055 9.055 0 0 1 1.82 -.18c3.6 0 6.6 2 9 6c-.666 1.11 -1.379 2.067 -2.138 2.87"/><path d="M3 3l18 18"/></svg>');
            }
            else if (passwordInput.attr('type') === 'text') {
                passwordInput.attr('type', 'password');
                $('#show_hide_icon').html('<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-eye tw-w-6" viewBox="0 0 24 24" stroke-width="1.5" stroke="#000000" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/></svg>');
            }
        });
        })
    </script>
@endsection
