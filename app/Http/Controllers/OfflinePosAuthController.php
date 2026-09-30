<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class OfflinePosAuthController extends Controller
{
    public function reauthenticate(Request $request)
    {
        $data = $request->validate([
            'business_id' => 'required|integer',
            'user_id' => 'required|integer',
            'credential_type' => 'required|in:pin,password',
            'credential' => 'required|string|max:255',
        ]);

        $user = User::where('id', $data['user_id'])
            ->where('business_id', $data['business_id'])
            ->where('status', 'active')
            ->where('allow_login', 1)
            ->whereHas('business', function ($query) {
                $query->where('is_active', 1);
            })
            ->first();
        $valid = false;

        if ($user && $data['credential_type'] === 'pin') {
            $stored = (string) $user->service_staff_pin;
            $valid = ! empty($user->is_enable_service_staff_pin) && $stored !== '' &&
                (hash_equals($stored, $data['credential']) ||
                    ((str_starts_with($stored, '$2y$') || str_starts_with($stored, '$argon2')) && Hash::check($data['credential'], $stored)));
        } elseif ($user) {
            $valid = Hash::check($data['credential'], $user->password);
        }

        if (! $valid) {
            return response()->json(['success' => false, 'msg' => __('auth.failed')], 422);
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return response()->json(['success' => true, 'csrf_token' => csrf_token()]);
    }
}
