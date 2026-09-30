@inject('request', 'Illuminate\Http\Request')

@if (
    ($request->segment(1) == 'pos' &&
        ($request->segment(2) == 'create' || $request->segment(3) == 'edit' || $request->segment(2) == 'payment')) ||
        $request->is('sells/pos/create'))
    @php
        $pos_layout = true;
    @endphp
@else
    @php
        $pos_layout = false;
    @endphp
@endif

@php
    $whitelist = ['127.0.0.1', '::1'];
@endphp

<!DOCTYPE html>
<html class="tw-bg-white tw-scroll-smooth" lang="{{ app()->getLocale() }}"
    dir="{{ in_array(session()->get('user.language', config('app.locale')), config('constants.langs_rtl')) ? 'rtl' : 'ltr' }}">
<head>
    <!-- Tell the browser to be responsive to screen width -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no"
        name="viewport">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('layouts.partials.pwa')
    
    <title>@yield('title') - {{ Session::get('business.name') }}</title>

    @include('layouts.partials.css')
    

    @include('layouts.partials.extracss')

    @yield('css')

</head>
<body
    class="tw-font-sans tw-antialiased tw-text-gray-900 tw-bg-gray-100 @if ($pos_layout) hold-transition lockscreen @else hold-transition skin-@if (!empty(session('business.theme_color'))){{ session('business.theme_color') }}@else{{ 'blue-light' }} @endif sidebar-mini @endif" >
    <div class="tw-flex thetop @if(!$pos_layout) independent-scroll-layout @endif">
        <script type="text/javascript">
            if (localStorage.getItem("upos_sidebar_collapse") == 'true') {
                var body = document.getElementsByTagName("body")[0];
                body.className += " sidebar-collapse";
            }
        </script>
        @if (!$pos_layout && $request->segment(1) != 'customer-display')
            @include('layouts.partials.sidebar')
        @endif

        @if (in_array($_SERVER['REMOTE_ADDR'], $whitelist))
            <input type="hidden" id="__is_localhost" value="true">
        @endif

        <!-- Add currency related field-->
        <input type="hidden" id="__code" value="{{ session('currency')['code'] }}">
        <input type="hidden" id="__symbol" value="{{ session('currency')['symbol'] }}">
        <input type="hidden" id="__thousand" value="{{ session('currency')['thousand_separator'] }}">
        <input type="hidden" id="__decimal" value="{{ session('currency')['decimal_separator'] }}">
        <input type="hidden" id="__symbol_placement" value="{{ session('business.currency_symbol_placement') }}">
        <input type="hidden" id="__precision" value="{{ session('business.currency_precision', 2) }}">
        <input type="hidden" id="__quantity_precision" value="{{ session('business.quantity_precision', 2) }}">
        <!-- End of currency related field-->
        @can('view_export_buttons')
            <input type="hidden" id="view_export_buttons">
        @endcan
        @if (isMobile())
            <input type="hidden" id="__is_mobile">
        @endif
        @if (session('status'))
            <input type="hidden" id="status_span" data-status="{{ session('status.success') }}"
                data-msg="{{ session('status.msg') }}">
        @endif
        <main class="tw-flex tw-flex-col tw-flex-1 tw-h-full tw-min-w-0 tw-bg-gray-100">
            @if($request->segment(1) != 'customer-display' && !$pos_layout)
                @include('layouts.partials.header')
            @elseif($request->segment(1) != 'customer-display')
                @include('layouts.partials.header-pos')
            @endif
            <!-- empty div for vuejs -->
            <div id="app">
                @yield('vue')
            </div>
            <div class="tw-flex-1 tw-overflow-y-auto tw-h-screen" id="scrollable-container">
                @yield('content')
                @if (!$pos_layout)
                
                    @include('layouts.partials.footer')
                @else
                    @include('layouts.partials.footer_pos')
                @endif
            </div>
            <div class='scrolltop no-print'>
                <div class='scroll icon'><i class="fas fa-angle-up"></i></div>
            </div>

            @if (config('constants.iraqi_selling_price_adjustment'))
                <input type="hidden" id="iraqi_selling_price_adjustment">
            @endif

            <!-- This will be printed -->
            <section class="invoice print_section" id="receipt_section">
            </section>
        </main>

        @include('home.todays_profit_modal')
        <!-- /.content-wrapper -->



        <audio id="success-audio">
            <source src="{{ asset('/audio/success.ogg?v=' . $asset_v) }}" type="audio/ogg">
            <source src="{{ asset('/audio/success.mp3?v=' . $asset_v) }}" type="audio/mpeg">
        </audio>
        <audio id="error-audio">
            <source src="{{ asset('/audio/error.ogg?v=' . $asset_v) }}" type="audio/ogg">
            <source src="{{ asset('/audio/error.mp3?v=' . $asset_v) }}" type="audio/mpeg">
        </audio>
        <audio id="warning-audio">
            <source src="{{ asset('/audio/warning.ogg?v=' . $asset_v) }}" type="audio/ogg">
            <source src="{{ asset('/audio/warning.mp3?v=' . $asset_v) }}" type="audio/mpeg">
        </audio>

        @if (!empty($__additional_html))
            {!! $__additional_html !!}
        @endif

        @include('layouts.partials.javascripts')

        @if ($pos_layout)
            <style>
                .pos-screen-lock {
                    display: none;
                    position: fixed;
                    inset: 0;
                    z-index: 99999;
                    background: rgba(17, 24, 39, 0.96);
                    color: #fff;
                }

                .pos-screen-lock.is-active {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 24px;
                }

                .pos-screen-lock-panel {
                    width: 100%;
                    max-width: 380px;
                    border-radius: 8px;
                    background: #fff;
                    color: #111827;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
                    padding: 24px;
                }

                .pos-screen-lock-title {
                    margin: 0 0 6px;
                    font-size: 22px;
                    font-weight: 700;
                }

                .pos-screen-lock-subtitle {
                    margin-bottom: 18px;
                    color: #6b7280;
                    font-size: 13px;
                }

                .pos-screen-lock-error {
                    display: none;
                    margin-bottom: 12px;
                }

                .pos-screen-lock-keypad {
                    display: grid;
                    grid-template-columns: repeat(3, 1fr);
                    gap: 8px;
                    margin-top: 10px;
                }

                .pos-screen-lock-keypad button {
                    height: 48px;
                    border: 1px solid #d1d5db;
                    border-radius: 6px;
                    background: #f9fafb;
                    font-size: 18px;
                    font-weight: 700;
                }

                .pos-screen-is-locked {
                    overflow: hidden;
                }
            </style>
            <div id="pos_screen_lock" class="pos-screen-lock" role="dialog" aria-modal="true" aria-labelledby="pos_screen_lock_title">
                <div class="pos-screen-lock-panel">
                    <h3 id="pos_screen_lock_title" class="pos-screen-lock-title">POS locked</h3>
                    <div class="pos-screen-lock-subtitle">
                        {{ auth()->user()->username ?? '' }}
                    </div>
                    <div class="alert alert-danger pos-screen-lock-error" id="pos_screen_lock_error"></div>
                    <form id="pos_screen_lock_form" autocomplete="off">
                        <input type="hidden" id="pos_screen_lock_credential_type" value="password">
                        <div id="pos_screen_lock_pin_group" class="form-group hide">
                            <label for="pos_screen_lock_pin">PIN</label>
                            <input type="password" inputmode="numeric" pattern="[0-9]*" maxlength="20" class="form-control input-lg text-center" id="pos_screen_lock_pin">
                            <div class="pos-screen-lock-keypad">
                                @foreach([1, 2, 3, 4, 5, 6, 7, 8, 9] as $key)
                                    <button type="button" data-pos-lock-key="{{ $key }}">{{ $key }}</button>
                                @endforeach
                                <button type="button" data-pos-lock-action="clear">Clear</button>
                                <button type="button" data-pos-lock-key="0">0</button>
                                <button type="button" data-pos-lock-action="backspace">Back</button>
                            </div>
                        </div>
                        <div id="pos_screen_lock_password_group" class="form-group">
                            <label for="pos_screen_lock_password">Password</label>
                            <input type="password" class="form-control input-lg" id="pos_screen_lock_password">
                        </div>
                        <button type="submit" class="btn btn-primary btn-block btn-lg" id="pos_screen_lock_unlock">Unlock</button>
                        <div class="row" style="margin-top: 12px;">
                            <div class="col-xs-6">
                                <button type="button" class="btn btn-default btn-block" id="pos_screen_lock_switch">Use PIN</button>
                            </div>
                            <div class="col-xs-6">
                                <button type="button" class="btn btn-default btn-block" id="pos_screen_lock_logout">Log out</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <script type="text/javascript">
                (function () {
                    var logoutUrl = @json(action([\App\Http\Controllers\Auth\LoginController::class, 'logout']));
                    var unlockUrl = @json(route('pos.unlock'));
                    var offlineReauthUrl = @json(route('pos.offline-reauthenticate'));
                    var pinUnlockAvailable = @json(!empty(auth()->user()->is_enable_service_staff_pin) && !empty(auth()->user()->service_staff_pin));
                    var lockTimer = null;
                    var timerEnabled = false;
                    var locked = false;
                    var lockStorageKey = 'ultimate_pos_screen_locked_' + (APP.USER_ID || 'guest');
                    var activityEvents = [
                        'click',
                        'keydown',
                        'mousemove',
                        'mousedown',
                        'scroll',
                        'touchstart',
                        'touchmove'
                    ];

                    function getTimeoutMinutes() {
                        var timeoutMinutes = parseInt($('#location_id').data('pos_inactivity_logout_minutes'), 10);

                        return isNaN(timeoutMinutes) ? 0 : timeoutMinutes;
                    }

                    function clearTimers() {
                        clearTimeout(lockTimer);
                    }

                    function persistLock() {
                        try {
                            sessionStorage.setItem(lockStorageKey, '1');
                        } catch (e) {}
                    }

                    function clearPersistedLock() {
                        try {
                            sessionStorage.removeItem(lockStorageKey);
                        } catch (e) {}
                    }

                    function hasPersistedLock() {
                        try {
                            return sessionStorage.getItem(lockStorageKey) === '1';
                        } catch (e) {
                            return false;
                        }
                    }

                    function logout() {
                        clearPersistedLock();
                        if (window.purgePosUserCache) {
                            window.purgePosUserCache();
                        }
                        var locationId = $('#location_id').val();
                        var separator = logoutUrl.indexOf('?') === -1 ? '?' : '&';
                        window.location.href = logoutUrl + (locationId ? separator + 'location_id=' + encodeURIComponent(locationId) : '');
                    }

                    function setCredentialType(type) {
                        $('#pos_screen_lock_credential_type').val(type);
                        $('#pos_screen_lock_pin_group').toggleClass('hide', type !== 'pin');
                        $('#pos_screen_lock_password_group').toggleClass('hide', type !== 'password');
                        $('#pos_screen_lock_switch').text(type === 'pin' ? 'Use password' : 'Use PIN');
                        $('#pos_screen_lock_pin, #pos_screen_lock_password').val('');
                        $('#pos_screen_lock_error').hide().text('');

                        setTimeout(function () {
                            if (type === 'pin') {
                                $('#pos_screen_lock_pin').focus();
                            } else {
                                $('#pos_screen_lock_password').focus();
                            }
                        }, 100);
                    }

                    function showLock(force) {
                        if (!timerEnabled && !force) {
                            return;
                        }

                        clearTimers();
                        locked = true;
                        persistLock();
                        setCredentialType(pinUnlockAvailable ? 'pin' : 'password');
                        $('body').addClass('pos-screen-is-locked');
                        $('#pos_screen_lock').addClass('is-active');
                    }

                    function hideLock() {
                        locked = false;
                        $('#pos_screen_lock').removeClass('is-active');
                        $('body').removeClass('pos-screen-is-locked');
                        $('#pos_screen_lock_pin, #pos_screen_lock_password').val('');
                        $('#pos_screen_lock_error').hide().text('');
                        clearPersistedLock();
                        resetTimer();
                    }

                    function showUnlockError(message) {
                        $('#pos_screen_lock_error').text(message || 'Unable to unlock POS.').show();
                    }

                    function resetTimer() {
                        if (locked) {
                            return;
                        }

                        clearTimers();

                        var timeoutMinutes = getTimeoutMinutes();
                        timerEnabled = timeoutMinutes > 0;

                        if (!timerEnabled) {
                            return;
                        }

                        lockTimer = setTimeout(showLock, timeoutMinutes * 60 * 1000);
                    }

                    activityEvents.forEach(function (eventName) {
                        document.addEventListener(eventName, function () {
                            if (!locked) {
                                resetTimer();
                            }
                        }, { passive: true });
                    });

                    $('#pos_screen_lock_switch').on('click', function () {
                        var currentType = $('#pos_screen_lock_credential_type').val();
                        setCredentialType(currentType === 'pin' ? 'password' : 'pin');
                    });

                    if (!pinUnlockAvailable) {
                        $('#pos_screen_lock_switch').closest('.col-xs-6').hide();
                        $('#pos_screen_lock_logout').closest('.col-xs-6').removeClass('col-xs-6').addClass('col-xs-12');
                    }

                    $('[data-pos-lock-key]').on('click', function () {
                        var pinInput = $('#pos_screen_lock_pin');
                        if (pinInput.val().length < 20) {
                            pinInput.val(pinInput.val() + $(this).data('pos-lock-key')).focus();
                        }
                    });

                    $('[data-pos-lock-action]').on('click', function () {
                        var pinInput = $('#pos_screen_lock_pin');
                        var action = $(this).data('pos-lock-action');

                        if (action === 'clear') {
                            pinInput.val('').focus();
                        } else if (action === 'backspace') {
                            pinInput.val(pinInput.val().slice(0, -1)).focus();
                        }
                    });

                    $('#pos_screen_lock_logout').on('click', function () {
                        logout();
                    });

                    $('#pos_manual_lock').on('click', function () {
                        showLock(true);
                    });

                    $('#pos_screen_lock_pin, #pos_screen_lock_password').on('keydown', function (event) {
                        if (event.key === 'Enter' || event.which === 13) {
                            event.preventDefault();
                            $('#pos_screen_lock_form').trigger('submit');
                        }
                    });

                    $('#pos_screen_lock_form').on('submit', function (event) {
                        event.preventDefault();

                        var credentialType = $('#pos_screen_lock_credential_type').val();
                        var credential = credentialType === 'pin'
                            ? $('#pos_screen_lock_pin').val()
                            : $('#pos_screen_lock_password').val();

                        if (!credential) {
                            showUnlockError('Enter your ' + (credentialType === 'pin' ? 'PIN' : 'password') + '.');
                            return;
                        }

                        $('#pos_screen_lock_unlock').prop('disabled', true);
                        $('#pos_screen_lock_error').hide().text('');

                        $.ajax({
                            method: 'POST',
                            url: window.posOfflineReauthRequired ? offlineReauthUrl : unlockUrl,
                            data: {
                                location_id: $('#location_id').val(),
                                credential_type: credentialType,
                                credential: credential,
                                business_id: $('#pos_sync_status').data('business-id'),
                                user_id: $('#pos_sync_status').data('user-id')
                            },
                            success: function (response) {
                                if (response.success) {
                                    if (response.csrf_token) {
                                        $('meta[name="csrf-token"]').attr('content', response.csrf_token);
                                        $.ajaxSetup({headers: {'X-CSRF-TOKEN': response.csrf_token}});
                                    }
                                    window.posOfflineReauthRequired = false;
                                    hideLock();
                                    window.dispatchEvent(new CustomEvent('pos:reauthenticated'));
                                } else {
                                    showUnlockError(response.msg);
                                }
                            },
                            error: function (xhr) {
                                var message = xhr.responseJSON && xhr.responseJSON.msg
                                    ? xhr.responseJSON.msg
                                    : 'Unable to unlock POS.';
                                showUnlockError(message);
                            },
                            complete: function () {
                                $('#pos_screen_lock_unlock').prop('disabled', false);
                            }
                        });
                    });

                    window.resetPosInactivityLogoutTimer = resetTimer;
                    window.lockPosScreen = function () {
                        showLock(true);
                    };
                    if (hasPersistedLock()) {
                        showLock(true);
                    } else {
                        resetTimer();
                    }
                })();
            </script>
        @endif
        
        {{-- Module JS --}}
        @include('layouts.module-assets')
        <div class="modal fade view_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

        @if (!empty($__additional_views) && is_array($__additional_views))
            @foreach ($__additional_views as $additional_view)
                @includeIf($additional_view)
            @endforeach
        @endif
        <div>

            <div class="overlay tw-hidden"></div>
        </div>
</body>
<style>
    @media print {
        #scrollable-container {
            overflow: visible !important;
            height: auto !important;
        }
        
        /* Hide side menu */
        .side-bar,
        .thetop > aside {
            display: none !important;
        }
    }
</style>
<style>
    .small-view-side-active {
        display: grid !important;
        z-index: 1000;
        position: absolute;
    }
    .overlay {
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.8);
        position: fixed;
        top: 0;
        left: 0;
        display: none;
        z-index: 20;
    }

    .tw-dw-btn.tw-dw-btn-xs.tw-dw-btn-outline {
        width: max-content;
        margin: 2px;
    }

    #scrollable-container{
        position:relative;
    }
    



</style>

</html>
