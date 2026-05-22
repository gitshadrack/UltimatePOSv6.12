<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\BusinessLocation;
use App\Providers\RouteServiceProvider;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Rules\ReCaptcha;


class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * All Utils instance.
     */
    protected $businessUtil;

    protected $moduleUtil;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(BusinessUtil $businessUtil, ModuleUtil $moduleUtil)
    {
        $this->middleware('guest')->except('logout');
        $this->businessUtil = $businessUtil;
        $this->moduleUtil = $moduleUtil;
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Change authentication from email to username
     *
     * @return void
     */
    public function username()
    {
        return 'username';
    }

    public function logout(Request $request)
    {
        if (auth()->check()) {
            $this->businessUtil->activityLog(auth()->user(), 'logout');
        }

        \Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    /**
     * The user has been authenticated.
     * Check if the business is active or not.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        $this->businessUtil->activityLog($user, 'login', null, [], false, $user->business_id);

        if (! $user->business->is_active) {
            $this->logoutAndInvalidateSession($request);

            return redirect('/login')
              ->with(
                  'status',
                  ['success' => 0, 'msg' => __('lang_v1.business_inactive')]
              );
        } elseif ($user->status != 'active') {
            $this->logoutAndInvalidateSession($request);

            return redirect('/login')
              ->with(
                  'status',
                  ['success' => 0, 'msg' => __('lang_v1.user_inactive')]
              );
        } elseif (! $user->allow_login) {
            $this->logoutAndInvalidateSession($request);

            return redirect('/login')
                ->with(
                    'status',
                    ['success' => 0, 'msg' => __('lang_v1.login_not_allowed')]
                );
        } elseif (($user->user_type == 'user_customer') && ! $this->moduleUtil->hasThePermissionInSubscription($user->business_id, 'crm_module')) {
            $this->logoutAndInvalidateSession($request);

            return redirect('/login')
                ->with(
                    'status',
                    ['success' => 0, 'msg' => __('lang_v1.business_dont_have_crm_subscription')]
                );
        }
    }

    protected function logoutAndInvalidateSession(Request $request)
    {
        \Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    protected function redirectTo()
    {
        $user = \Auth::user();
        if (! $user->can('dashboard.data') && $user->can('sell.create')) {
            return '/pos/create';
        }

        if ($user->user_type == 'user_customer') {
            return 'contact/contact-dashboard';
        }

        return '/home';
    }

    public function validateLogin(Request $request)
    {
        if ($request->input('login_type') == 'pin') {
            $rules = [
                'location_id' => 'required|integer',
                'pin' => 'required|digits_between:4,6',
            ];

            $this->validate($request, $rules);

            return;
        }

        if(config('constants.enable_recaptcha')){
            $this->validate($request, [
                $this->username() => 'required|string',
                'password' => 'required|string',
                'g-recaptcha-response' => ['required', new ReCaptcha]
            ]);
        }else{
            $this->validate($request, [
                $this->username() => 'required|string',
                'password' => 'required|string',
            ]);
        }
       
    }

    protected function attemptLogin(Request $request)
    {
        if ($request->input('login_type') == 'pin') {
            return $this->attemptPinLogin($request);
        }

        return $this->guard()->attempt(
            $this->credentials($request),
            $request->filled('remember')
        );
    }

    protected function attemptPinLogin(Request $request)
    {
        $location = BusinessLocation::with('business')
            ->Active()
            ->find($request->input('location_id'));

        if (empty($location) || empty($location->business)) {
            return false;
        }

        if (empty($location->enable_numeric_login)) {
            return false;
        }

        $pin = (string) $request->input('pin');
        $users = User::with('business')
            ->where('business_id', $location->business_id)
            ->where('allow_login', 1)
            ->where('status', 'active')
            ->where('is_enable_service_staff_pin', 1)
            ->whereNotNull('service_staff_pin')
            ->permission(['location.'.$location->id, 'access_all_locations'])
            ->get();

        $matched_users = $users->filter(function ($user) use ($pin) {
            $stored_pin = (string) $user->service_staff_pin;
            $pin_matches = hash_equals($stored_pin, $pin);

            if (! $pin_matches && (strpos($stored_pin, '$2y$') === 0 || strpos($stored_pin, '$argon2') === 0)) {
                $pin_matches = Hash::check($pin, $stored_pin);
            }

            return $pin_matches;
        });

        if ($matched_users->count() !== 1) {
            return false;
        }

        $user = $matched_users->first();
        $this->guard()->login($user, $request->filled('remember'));
        $request->session()->regenerate();
        $request->session()->put('numeric_login_location_id', $location->id);

        return true;
    }

}
