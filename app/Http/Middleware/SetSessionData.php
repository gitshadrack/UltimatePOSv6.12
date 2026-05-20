<?php

namespace App\Http\Middleware;

use App\Business;
use App\Currency;
use App\Utils\BusinessUtil;
use Closure;
use Illuminate\Support\Facades\Auth;

class SetSessionData
{
    /**
     * Checks if session data is set or not for a user. If data is not set then set it.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = Auth::user();
        $session_user_id = $request->session()->get('user.id');

        if (! $request->session()->has('user') || $session_user_id != $user->id) {
            $business_util = new BusinessUtil;

            $request->session()->forget(['user', 'business', 'currency', 'financial_year']);

            $session_data = ['id' => $user->id,
                'surname' => $user->surname,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'business_id' => $user->business_id,
                'language' => $user->language,
            ];
            $business = Business::findOrFail($user->business_id);

            $currency = $business->currency ?: Currency::first();
            $currency_data = ['id' => optional($currency)->id,
                'code' => optional($currency)->code,
                'symbol' => optional($currency)->symbol,
                'thousand_separator' => optional($currency)->thousand_separator,
                'decimal_separator' => optional($currency)->decimal_separator,
            ];

            $request->session()->put('user', $session_data);
            $request->session()->put('business', $business);
            $request->session()->put('currency', $currency_data);

            //set current financial year to session
            $financial_year = $business_util->getCurrentFinancialYear($business->id);
            $request->session()->put('financial_year', $financial_year);
        }

        return $next($request);
    }
}
