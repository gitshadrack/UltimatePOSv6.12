<?php

namespace Modules\PageSpeed\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class PageSpeedService
{
    protected $settings;
    protected $business_id;

    public function __construct()
    {
        try {
            // Try to get business_id from session first
            $this->business_id = session()->get('user.business_id');

            // If not in session, try from authenticated user
            if (!$this->business_id && auth()->check() && auth()->user()) {
                $this->business_id = auth()->user()->business_id ?? null;
            }

            $this->loadSettings();
        } catch (\Exception $e) {
            // Safe fallback if session not available
            $this->business_id = null;
            $this->settings = null;
        }
    }

    /**
     * Load settings from database
     */
    protected function loadSettings()
    {
        try {
            if ($this->business_id) {
                // Load settings for specific business
                $this->settings = DB::table('pagespeed_settings')
                    ->where('business_id', $this->business_id)
                    ->first();
            } else {
                // For public pages (no business_id), check if any business has it enabled
                // If so, use default optimization settings
                $anyEnabled = DB::table('pagespeed_settings')
                    ->where('enabled', 1)
                    ->exists();

                if ($anyEnabled) {
                    // Use first enabled business settings as default for public pages
                    $this->settings = DB::table('pagespeed_settings')
                        ->where('enabled', 1)
                        ->first();
                }
            }
        } catch (\Exception $e) {
            // Table might not exist yet
            $this->settings = null;
        }
    }

    /**
     * Check if optimization is enabled
     */
    public function isEnabled()
    {
        return $this->settings && $this->settings->enabled;
    }

    /**
     * Optimize HTML content
     */
    public function optimizeHTML($html)
    {
        if (!$this->isEnabled()) {
            return $html;
        }

        // Minify CSS in <style> tags (SAFE)
        if ($this->settings->minify_css) {
            $html = $this->minifyInlineCSS($html);
        }

        // Minify HTML
        if ($this->settings->minify_html) {
            $html = $this->minifyHTML($html);
        }

        // Add lazy loading to images
        if ($this->settings->lazy_load_images) {
            $html = $this->addLazyLoading($html);
        }

        // Defer JavaScript
        if ($this->settings->defer_js) {
            $html = $this->deferJavaScript($html);
        }

        // Add DNS prefetch
        if ($this->settings->dns_prefetch_enabled) {
            $html = $this->addDNSPrefetch($html);
        }

        // Remove query strings
        if ($this->settings->remove_query_strings) {
            $html = $this->removeQueryStrings($html);
        }

        // Add Resource Hints
        $html = $this->addResourceHints($html);

        return $html;
    }

    /**
     * Minify HTML - COMPLETELY DISABLED
     * تم تعطيل جميع تحسينات HTML لأنها تكسر الصفحات
     */
    protected function minifyHTML($html)
    {
        // DISABLED: HTML minification breaks DataTables, jQuery plugins, and inline handlers
        // Only cache optimization is enabled for safety
        return $html;
    }

    /**
     * Add lazy loading to images - DISABLED for compatibility
     */
    protected function addLazyLoading($html)
    {
        // DISABLED: May break images loaded by JavaScript
        return $html;
    }

    /**
     * Defer JavaScript loading - DISABLED to prevent breaking functionality
     * تم تعطيل defer للسكريبتات لأنه يكسر ترتيب التنفيذ
     */
    protected function deferJavaScript($html)
    {
        // DISABLED: defer can break script execution order
        // This was causing sidebar menu and statistics to not work
        return $html;
    }

    /**
     * Add DNS prefetch - DISABLED
     */
    protected function addDNSPrefetch($html)
    {
        // DISABLED for safety
        return $html;
    }

    /**
     * Remove query strings from static resources - DISABLED
     */
    protected function removeQueryStrings($html)
    {
        // DISABLED: Query strings are used for cache busting (?v=xxxx)
        // Removing them breaks asset versioning
        return $html;
    }

    /**
     * Get cached page
     */
    public function getCachedPage($url, $isMobile = false, $isLoggedIn = false)
    {
        if (!$this->settings || !$this->settings->page_cache_enabled) {
            return null;
        }

        // Don't cache for logged in users if disabled
        if ($isLoggedIn && !$this->settings->cache_logged_in_users) {
            return null;
        }

        $cache_key = $this->getCacheKey($url, $isMobile, $isLoggedIn);

        $cached = DB::table('pagespeed_cache')
            ->where('cache_key', $cache_key)
            ->where('expires_at', '>', now())
            ->first();

        if ($cached) {
            // Increment hit counter
            DB::table('pagespeed_cache')
                ->where('id', $cached->id)
                ->increment('hits');

            // Update statistics
            DB::table('pagespeed_settings')
                ->where('business_id', $this->business_id)
                ->increment('cache_hits');

            return $cached->content;
        }

        return null;
    }

    /**
     * Cache page content
     */
    public function cachePage($url, $content, $isMobile = false, $isLoggedIn = false)
    {
        if (!$this->settings || !$this->settings->page_cache_enabled) {
            return;
        }

        $cache_key = $this->getCacheKey($url, $isMobile, $isLoggedIn);
        $lifetime = $this->settings->page_cache_lifetime ?? 86400;

        DB::table('pagespeed_cache')->updateOrInsert(
            ['cache_key' => $cache_key],
            [
                'url' => $url,
                'content' => $content,
                'is_mobile' => $isMobile,
                'is_logged_in' => $isLoggedIn,
                'expires_at' => now()->addSeconds($lifetime),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    /**
     * Generate cache key
     */
    protected function getCacheKey($url, $isMobile, $isLoggedIn)
    {
        $key = 'pagespeed_' . md5($url);

        if ($this->settings->cache_mobile_separate && $isMobile) {
            $key .= '_mobile';
        }

        if ($isLoggedIn) {
            $key .= '_logged_in';
        }

        return $key;
    }

    /**
     * Clear all cache
     */
    public function clearAllCache()
    {
        DB::table('pagespeed_cache')->truncate();
        Cache::flush();

        return true;
    }

    /**
     * Clear cache for specific URL
     */
    public function clearCacheForUrl($url)
    {
        DB::table('pagespeed_cache')->where('url', $url)->delete();

        return true;
    }

    /**
     * Get statistics
     *
     * @param int|null $business_id Optional business ID, uses session business_id if not provided
     * @return array|null
     */
    public function getStatistics($business_id = null)
    {
        // Use provided business_id or fall back to instance business_id
        $businessId = $business_id ?? $this->business_id;

        // If still no business_id, try to get from session directly
        if (!$businessId) {
            $businessId = session()->get('user.business_id');
        }

        // If still no business_id, try from authenticated user
        if (!$businessId && auth()->check() && auth()->user()) {
            $businessId = auth()->user()->business_id ?? null;
        }

        // If still no business_id, use first enabled business (same as recordPerformance)
        if (!$businessId) {
            try {
                $firstEnabled = DB::table('pagespeed_settings')
                    ->where('enabled', 1)
                    ->first();

                if ($firstEnabled) {
                    $businessId = $firstEnabled->business_id;
                }
            } catch (\Exception $e) {
                // Table might not exist yet
            }
        }

        // If still no business_id, return default empty stats
        if (!$businessId) {
            return [
                'cache_hits' => 0,
                'total_requests' => 0,
                'avg_load_time' => 0,
                'cache_count' => 0,
                'cache_size' => 0,
                'hit_ratio' => 0,
            ];
        }

        try {
            $stats = DB::table('pagespeed_settings')
                ->where('business_id', $businessId)
                ->first();

            $cache_count = DB::table('pagespeed_cache')->count();
            $cache_size = DB::table('pagespeed_cache')
                ->selectRaw('SUM(LENGTH(content)) as total_size')
                ->value('total_size');

            return [
                'cache_hits' => $stats->cache_hits ?? 0,
                'total_requests' => $stats->total_requests ?? 0,
                'avg_load_time' => $stats->avg_load_time ?? 0,
                'cache_count' => $cache_count,
                'cache_size' => $cache_size ? round($cache_size / 1024 / 1024, 2) : 0, // MB
                'hit_ratio' => $stats && $stats->total_requests > 0 ? round(($stats->cache_hits / $stats->total_requests) * 100, 2) : 0,
            ];
        } catch (\Exception $e) {
            \Log::error('PageSpeed getStatistics error: ' . $e->getMessage());
            return [
                'cache_hits' => 0,
                'total_requests' => 0,
                'avg_load_time' => 0,
                'cache_count' => 0,
                'cache_size' => 0,
                'hit_ratio' => 0,
            ];
        }
    }

    /**
     * Minify inline CSS in <style> tags
     *
     * آمن جداً - يصغر CSS فقط دون المساس بـ JavaScript أو HTML
     * يقلل حجم CSS بنسبة 30-40%
     */
    protected function minifyInlineCSS($html)
    {
        // Find all <style> tags
        $html = preg_replace_callback(
            '/<style[^>]*>(.*?)<\/style>/is',
            function ($matches) {
                $css = $matches[1];

                // Remove comments
                $css = preg_replace('/\/\*.*?\*\//s', '', $css);

                // Remove whitespace around { } : ; ,
                $css = preg_replace('/\s*([{}:;,])\s*/', '$1', $css);

                // Remove multiple spaces
                $css = preg_replace('/\s+/', ' ', $css);

                // Remove spaces before and after >
                $css = preg_replace('/\s*>\s*/', '>', $css);

                // Trim
                $css = trim($css);

                return '<style' . substr($matches[0], 6, strpos($matches[0], '>') - 6) . '>' . $css . '</style>';
            },
            $html
        );

        return $html;
    }

    /**
     * Add Resource Hints to HTML
     *
     * يضيف dns-prefetch و preconnect لتسريع تحميل الموارد الخارجية
     * آمن جداً ويحسن الأداء بشكل ملحوظ
     */
    protected function addResourceHints($html)
    {
        // Extract all external domains from the HTML
        $domains = [];

        // Find external CSS links
        preg_match_all('/<link[^>]+href=["\'](https?:)?\/\/([^"\'\/]+)/i', $html, $matches);
        if (!empty($matches[2])) {
            $domains = array_merge($domains, $matches[2]);
        }

        // Find external script sources
        preg_match_all('/<script[^>]+src=["\'](https?:)?\/\/([^"\'\/]+)/i', $html, $matches);
        if (!empty($matches[2])) {
            $domains = array_merge($domains, $matches[2]);
        }

        // Find external images
        preg_match_all('/<img[^>]+src=["\'](https?:)?\/\/([^"\'\/]+)/i', $html, $matches);
        if (!empty($matches[2])) {
            $domains = array_merge($domains, $matches[2]);
        }

        // Remove duplicates and filter out current domain
        $domains = array_unique($domains);
        $currentDomain = parse_url(url('/'), PHP_URL_HOST);
        $domains = array_filter($domains, function ($domain) use ($currentDomain) {
            return $domain !== $currentDomain && !empty($domain);
        });

        if (empty($domains)) {
            return $html;
        }

        // Build resource hints
        $hints = [];
        foreach ($domains as $domain) {
            // DNS Prefetch - fastest, least resource intensive
            $hints[] = '<link rel="dns-prefetch" href="//' . htmlspecialchars($domain) . '">';

            // Preconnect for critical domains (fonts, CDNs)
            if (stripos($domain, 'font') !== false ||
                stripos($domain, 'cdn') !== false ||
                stripos($domain, 'googleapis') !== false ||
                stripos($domain, 'cloudflare') !== false) {
                $hints[] = '<link rel="preconnect" href="//' . htmlspecialchars($domain) . '" crossorigin>';
            }
        }

        // Insert hints after <head> tag
        if (!empty($hints)) {
            $hintsHtml = "\n    " . implode("\n    ", $hints) . "\n";
            $html = preg_replace('/(<head[^>]*>)/i', '$1' . $hintsHtml, $html, 1);
        }

        return $html;
    }
}
