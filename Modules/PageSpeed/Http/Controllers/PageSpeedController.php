<?php

namespace Modules\PageSpeed\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\PageSpeed\Services\PageSpeedService;

class PageSpeedController extends Controller
{
    protected $pageSpeedService;

    public function __construct(PageSpeedService $pageSpeedService)
    {
        $this->pageSpeedService = $pageSpeedService;
    }

    /**
     * Display main settings page
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (! auth()->user()->can('pagespeed.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        // If no business_id in session, try from auth user
        if (!$business_id && auth()->check() && auth()->user()) {
            $business_id = auth()->user()->business_id ?? null;
        }

        // If still no business_id, use first enabled business
        if (!$business_id) {
            $firstEnabled = DB::table('pagespeed_settings')
                ->where('enabled', 1)
                ->first();

            if ($firstEnabled) {
                $business_id = $firstEnabled->business_id;
            }
        }

        $settings = DB::table('pagespeed_settings')
            ->where('business_id', $business_id)
            ->first();

        $statistics = $this->pageSpeedService->getStatistics();

        return view('pagespeed::settings.index', compact('settings', 'statistics'));
    }

    /**
     * Update settings
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateSettings(Request $request)
    {
        if (! auth()->user()->can('pagespeed.manage_settings')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            $data = [
                'enabled' => $request->has('enabled'),
                'page_cache_enabled' => $request->has('page_cache_enabled'),
                'page_cache_lifetime' => $request->input('page_cache_lifetime', 86400),
                'database_cache_enabled' => $request->has('database_cache_enabled'),
                'cache_mobile_separate' => $request->has('cache_mobile_separate'),
                'cache_logged_in_users' => $request->has('cache_logged_in_users'),
                'minify_html' => $request->has('minify_html'),
                'remove_html_comments' => $request->has('remove_html_comments'),
                'minify_css' => $request->has('minify_css'),
                'combine_css' => $request->has('combine_css'),
                'inline_critical_css' => $request->has('inline_critical_css'),
                'minify_js' => $request->has('minify_js'),
                'combine_js' => $request->has('combine_js'),
                'defer_js' => $request->has('defer_js'),
                'lazy_load_images' => $request->has('lazy_load_images'),
                'lazy_load_iframes' => $request->has('lazy_load_iframes'),
                'exclude_first_images' => $request->input('exclude_first_images', 3),
                'gzip_enabled' => $request->has('gzip_enabled'),
                'gzip_level' => $request->input('gzip_level', 6),
                'browser_cache_enabled' => $request->has('browser_cache_enabled'),
                'preload_enabled' => $request->has('preload_enabled'),
                'dns_prefetch_enabled' => $request->has('dns_prefetch_enabled'),
                'cdn_enabled' => $request->has('cdn_enabled'),
                'cdn_url' => $request->input('cdn_url'),
                'remove_query_strings' => $request->has('remove_query_strings'),
                'disable_emojis' => $request->has('disable_emojis'),
                'updated_at' => now(),
            ];

            DB::table('pagespeed_settings')
                ->where('business_id', $business_id)
                ->update($data);

            $output = [
                'success' => true,
                'msg' => __('pagespeed::lang.settings_updated')
            ];
        } catch (\Exception $e) {
            \Log::error('PageSpeed settings update error: ' . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->back()->with('status', $output);
    }

    /**
     * Clear all cache
     *
     * @return \Illuminate\Http\Response
     */
    public function clearCache()
    {
        if (! auth()->user()->can('pagespeed.clear_cache')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $this->pageSpeedService->clearAllCache();

            $output = [
                'success' => true,
                'msg' => __('pagespeed::lang.cache_cleared')
            ];
        } catch (\Exception $e) {
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->back()->with('status', $output);
    }

    /**
     * Get statistics
     *
     * @return \Illuminate\Http\Response
     */
    public function getStatistics()
    {
        if (! auth()->user()->can('pagespeed.view_statistics')) {
            abort(403, 'Unauthorized action.');
        }

        $statistics = $this->pageSpeedService->getStatistics();

        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }
}
