<?php

namespace Modules\PageSpeed\Http\Controllers;

use App\System;
use Composer\Semver\Comparator;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class InstallController extends Controller
{
    public function __construct()
    {
        $this->module_name = 'pagespeed';
        $this->appVersion = config('pagespeed.module_version');
        $this->module_display_name = 'PageSpeed';
    }

    /**
     * Install
     *
     * @return Response
     */
    public function index()
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '512M');

        $this->installSettings();

        //Check if PageSpeed installed or not.
        $is_installed = System::getProperty($this->module_name.'_version');
        if (empty($is_installed)) {
            DB::statement('SET default_storage_engine=INNODB;');
            Artisan::call('module:migrate', ['module' => 'PageSpeed', '--force' => true]);
            Artisan::call('module:publish', ['module' => 'PageSpeed']);
            System::addProperty($this->module_name.'_version', $this->appVersion);

            // Create default settings for all businesses
            $businesses = \App\Business::all();
            foreach ($businesses as $business) {
                DB::table('pagespeed_settings')->insert([
                    'business_id' => $business->id,
                    'enabled' => true,
                    'page_cache_enabled' => true,
                    'minify_html' => true,
                    'minify_css' => true,
                    'minify_js' => true,
                    'lazy_load_images' => true,
                    'gzip_enabled' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Clear all caches
            Artisan::call('cache:clear');
            Artisan::call('view:clear');
            Artisan::call('config:clear');
        }

        $output = ['success' => 1,
            'msg' => 'PageSpeed module installed successfully! Your website is now optimized for maximum performance.',
        ];

        return redirect()
            ->action([\App\Http\Controllers\Install\ModulesController::class, 'index'])
            ->with('status', $output);
    }

    /**
     * Initialize all install functions
     */
    private function installSettings()
    {
        config(['app.debug' => true]);
        Artisan::call('config:clear');
    }

    /**
     * Uninstall
     *
     * @return Response
     */
    public function uninstall()
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            System::removeProperty($this->module_name.'_version');

            // Clear all PageSpeed caches
            DB::table('pagespeed_cache')->truncate();

            $output = ['success' => true,
                'msg' => __('lang_v1.success'),
            ];
        } catch (\Exception $e) {
            $output = ['success' => false,
                'msg' => $e->getMessage(),
            ];
        }

        return redirect()->back()->with(['status' => $output]);
    }

    /**
     * update module
     *
     * @return Response
     */
    public function update()
    {
        //Check if pagespeed_version is same as appVersion then 404
        //If appVersion > pagespeed_version - run update script.
        //Else there is some problem.
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', '512M');

            $pagespeed_version = System::getProperty($this->module_name.'_version');

            if (Comparator::greaterThan($this->appVersion, $pagespeed_version)) {
                ini_set('max_execution_time', 0);
                ini_set('memory_limit', '512M');
                $this->installSettings();

                DB::statement('SET default_storage_engine=INNODB;');
                Artisan::call('module:migrate', ['module' => 'PageSpeed', '--force' => true]);
                System::setProperty($this->module_name.'_version', $this->appVersion);

                // Clear all caches after update
                Artisan::call('cache:clear');
                Artisan::call('view:clear');
            } else {
                abort(404);
            }

            $output = ['success' => 1,
                'msg' => 'PageSpeed module updated successfully to version '.$this->appVersion.' !!',
            ];

            return redirect()->back()->with(['status' => $output]);
        } catch (\Exception $e) {
            exit($e->getMessage());
        }
    }
}
