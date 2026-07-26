<?php

namespace Modules\Superadmin\Http\Controllers;

use Illuminate\Foundation\Http\MaintenanceModeBypassCookie;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class MaintenanceModeController extends Controller
{
    /**
     * Display the system-wide maintenance controls.
     */
    public function index()
    {
        $this->authorizeSuperadmin();

        $is_down = app()->isDownForMaintenance();
        $maintenance_data = $is_down ? app()->maintenanceMode()->data() : [];

        return view('superadmin::maintenance.index')
            ->with(compact('is_down', 'maintenance_data'));
    }

    /**
     * Put the entire application into Laravel maintenance mode.
     */
    public function enable(Request $request)
    {
        $this->authorizeSuperadmin();
        $this->ensureFeatureIsAvailable();

        $validated = $request->validate([
            'admin_password' => ['required', 'string'],
            'confirm_text' => ['required', 'in:MAINTENANCE'],
            'retry_seconds' => ['required', 'integer', 'min:30', 'max:3600'],
            'backup_confirmed' => ['accepted'],
            'users_notified' => ['accepted'],
        ]);

        if (! Hash::check($validated['admin_password'], auth()->user()->password)) {
            return back()
                ->withInput($request->except('admin_password'))
                ->with('status', [
                    'success' => 0,
                    'msg' => __('superadmin::lang.invalid_admin_password'),
                ]);
        }

        if (app()->isDownForMaintenance()) {
            return back()->with('status', [
                'success' => 0,
                'msg' => __('superadmin::lang.maintenance_already_enabled'),
            ]);
        }

        try {
            $secret = Str::random(48);
            $exit_code = Artisan::call('down', [
                '--secret' => $secret,
                '--retry' => (int) $validated['retry_seconds'],
                '--status' => 503,
                '--render' => 'errors::503',
            ]);

            if ($exit_code !== 0 || ! app()->isDownForMaintenance()) {
                throw new RuntimeException(trim(Artisan::output()) ?: 'Maintenance mode was not enabled.');
            }

            Log::warning('System maintenance mode enabled from Superadmin.', [
                'user_id' => auth()->id(),
                'username' => auth()->user()->username,
                'ip_address' => $request->ip(),
                'retry_seconds' => (int) $validated['retry_seconds'],
            ]);

            return redirect()
                ->route('superadmin.maintenance.index')
                ->withCookie(MaintenanceModeBypassCookie::create($secret))
                ->with('maintenance_bypass_url', url($secret))
                ->with('status', [
                    'success' => 1,
                    'msg' => __('superadmin::lang.maintenance_enabled_successfully'),
                ]);
        } catch (\Throwable $e) {
            Log::emergency('Unable to enable system maintenance mode.', [
                'user_id' => auth()->id(),
                'ip_address' => $request->ip(),
                'message' => $e->getMessage(),
            ]);

            return back()
                ->withInput($request->except('admin_password'))
                ->with('status', [
                    'success' => 0,
                    'msg' => __('superadmin::lang.maintenance_change_failed'),
                ]);
        }
    }

    /**
     * Return the application to normal operation.
     */
    public function disable(Request $request)
    {
        $this->authorizeSuperadmin();
        $this->ensureFeatureIsAvailable();

        $validated = $request->validate([
            'admin_password' => ['required', 'string'],
            'confirm_text' => ['required', 'in:ONLINE'],
        ]);

        if (! Hash::check($validated['admin_password'], auth()->user()->password)) {
            return back()
                ->with('status', [
                    'success' => 0,
                    'msg' => __('superadmin::lang.invalid_admin_password'),
                ]);
        }

        if (! app()->isDownForMaintenance()) {
            return back()->with('status', [
                'success' => 0,
                'msg' => __('superadmin::lang.maintenance_already_disabled'),
            ]);
        }

        try {
            $exit_code = Artisan::call('up');

            if ($exit_code !== 0 || app()->isDownForMaintenance()) {
                throw new RuntimeException(trim(Artisan::output()) ?: 'Maintenance mode was not disabled.');
            }

            Log::warning('System maintenance mode disabled from Superadmin.', [
                'user_id' => auth()->id(),
                'username' => auth()->user()->username,
                'ip_address' => $request->ip(),
            ]);

            return redirect()
                ->route('superadmin.maintenance.index')
                ->withCookie(Cookie::forget(
                    'laravel_maintenance',
                    config('session.path'),
                    config('session.domain')
                ))
                ->with('status', [
                    'success' => 1,
                    'msg' => __('superadmin::lang.maintenance_disabled_successfully'),
                ]);
        } catch (\Throwable $e) {
            Log::emergency('Unable to disable system maintenance mode.', [
                'user_id' => auth()->id(),
                'ip_address' => $request->ip(),
                'message' => $e->getMessage(),
            ]);

            return back()->with('status', [
                'success' => 0,
                'msg' => __('superadmin::lang.maintenance_change_failed'),
            ]);
        }
    }

    /**
     * This controller sits behind the superadmin middleware, but the explicit
     * ability check protects direct controller calls as well.
     */
    private function authorizeSuperadmin(): void
    {
        if (! auth()->user() || ! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function ensureFeatureIsAvailable(): void
    {
        if (config('app.env') === 'demo') {
            abort(403, 'Feature disabled in demo mode.');
        }
    }
}
