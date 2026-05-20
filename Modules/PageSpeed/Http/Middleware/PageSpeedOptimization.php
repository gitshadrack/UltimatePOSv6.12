<?php

namespace Modules\PageSpeed\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\PageSpeed\Services\PageSpeedService;

class PageSpeedOptimization
{
    protected $pageSpeedService;

    public function __construct()
    {
        try {
            $this->pageSpeedService = app('pagespeed');
        } catch (\Exception $e) {
            $this->pageSpeedService = null;
        }
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Record start time ALWAYS for performance monitoring
        $startTime = microtime(true);

        // Skip if service not available
        if (!$this->pageSpeedService) {
            return $next($request);
        }

        // Check if optimization is enabled
        if (!$this->pageSpeedService->isEnabled()) {
            return $next($request);
        }

        // Skip optimization for excluded URLs
        if ($this->shouldSkipOptimization($request)) {
            return $next($request);
        }

        // Use URL without query parameters for cache key
        // This allows same HTML skeleton to be cached regardless of pagination/filters
        // AJAX will load dynamic data (DataTables, filters, etc.)
        $url = $request->url(); // Without query parameters
        $isMobile = $this->isMobile($request);
        $isLoggedIn = auth()->check();

        // Try to get cached page
        $cachedContent = $this->pageSpeedService->getCachedPage($url, $isMobile, $isLoggedIn);

        if ($cachedContent) {
            $loadTime = (microtime(true) - $startTime) * 1000;

            // Record performance for cache HIT
            $this->recordPerformance($loadTime, true); // true = cache hit

            // Apply Gzip compression if supported
            $compressionResult = $this->applyGzipCompression($request, $cachedContent);

            $response = response($compressionResult['content']);
            $response->header('X-PageSpeed-Cache', 'HIT');
            $response->header('X-PageSpeed-Time', round($loadTime, 2) . 'ms');
            $response->header('X-PageSpeed-Status', 'ACTIVE');

            // Add compression headers if content was compressed
            if ($compressionResult['compressed']) {
                $response->header('Content-Encoding', 'gzip');
                $response->header('X-PageSpeed-Compression', 'gzip');
            }

            // Add browser caching headers
            $this->addBrowserCachingHeaders($response);

            return $response;
        }

        // Process the request
        $response = $next($request);

        // Only optimize HTML responses
        if ($this->shouldOptimizeResponse($response)) {
            $content = $response->getContent();

            // Apply optimizations
            $optimizedContent = $this->pageSpeedService->optimizeHTML($content);

            // Cache the optimized page
            $this->pageSpeedService->cachePage($url, $optimizedContent, $isMobile, $isLoggedIn);

            // Calculate and record load time
            $loadTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

            $this->recordPerformance($loadTime, false); // false = cache miss

            // Apply Gzip compression if supported
            $compressionResult = $this->applyGzipCompression($request, $optimizedContent);

            $response->setContent($compressionResult['content']);
            $response->header('X-PageSpeed-Cache', 'MISS');
            $response->header('X-PageSpeed-Time', round($loadTime, 2) . 'ms');
            $response->header('X-PageSpeed-Status', 'ACTIVE');
            $response->header('X-PageSpeed-Original-Size', strlen($content) . 'B');
            $response->header('X-PageSpeed-Optimized-Size', strlen($optimizedContent) . 'B');

            // Add compression headers if content was compressed
            if ($compressionResult['compressed']) {
                $response->header('Content-Encoding', 'gzip');
                $response->header('X-PageSpeed-Compression', 'gzip');
                $compressedSize = strlen($compressionResult['content']);
                $compressionRatio = round((1 - ($compressedSize / strlen($optimizedContent))) * 100, 1);
                $response->header('X-PageSpeed-Compression-Ratio', $compressionRatio . '%');
            }

            $savings = strlen($content) - strlen($optimizedContent);
            $savingsPercent = $content ? round(($savings / strlen($content)) * 100, 1) : 0;
            $response->header('X-PageSpeed-Savings', $savings . 'B (' . $savingsPercent . '%)');

            // Add browser caching headers
            $this->addBrowserCachingHeaders($response);
        }

        return $response;
    }

    /**
     * Check if optimization should be skipped
     *
     * الاستراتيجية الصحيحة:
     * - معظم صفحات النظام = HTML skeleton (ثابت قابل للـ cache) + AJAX data (ديناميكي)
     * - نقوم بـ cache الـ HTML skeleton لجميع المستخدمين
     * - الـ AJAX requests تُستثنى تلقائياً (تحميل البيانات الديناميكية)
     * - نستثني فقط الصفحات الحرجة جداً (forms و real-time pages)
     */
    protected function shouldSkipOptimization(Request $request)
    {
        $path = $request->path();

        // Never cache pages for an active Laravel browser session.
        // Admin forms contain CSRF tokens; cached tokens cause 419 Page Expired.
        $sessionCookie = config('session.cookie');
        if (! empty($sessionCookie) && $request->cookies->has($sessionCookie)) {
            return true;
        }

        // 1. Skip AJAX requests - هذه تُحمّل البيانات الديناميكية للـ DataTables
        if ($request->ajax() || $request->wantsJson()) {
            return true;
        }

        // 2. Only optimize GET requests
        if ($request->method() !== 'GET') {
            return true;
        }

        // 3. استثني الصفحات الحرجة جداً (forms و real-time pages فقط)
        $critical_paths = [
            'login',
            'register',
            'logout',
            'password',
            'roles',
            'users',
            'business',
            'modules',
            'pagespeed',            // PageSpeed settings page - shows dynamic statistics
            'pos/create',           // نقطة البيع - real-time
            'pos/edit',
            'sells/create',         // إنشاء مبيعة - form
            'sells/edit',
            'sells/pos',            // POS page
            'purchases/create',     // إنشاء مشتريات - form
            'purchases/edit',
            'cash-register',        // سجل النقدية - real-time
            'manufacturing/production', // إنتاج - real-time
            'stock-adjustments/create',
            'stock-adjustments/edit',
            'stock-transfers/create',
            'stock-transfers/edit',
            'api',                  // API endpoints
            'ajax',                 // AJAX endpoints
        ];

        foreach ($critical_paths as $critical) {
            if ($path === $critical || str_starts_with($path, $critical . '/')) {
                return true;
            }
        }

        // 4. استثني من الـ config
        $config_excluded = config('pagespeed.exclusions.urls', []);
        foreach ($config_excluded as $excluded) {
            if (str_starts_with($path, trim($excluded, '/'))) {
                return true;
            }
        }

        // 5. Cache everything else (HTML skeleton):
        // ✅ Dashboard (home)
        // ✅ Product list (products) - HTML skeleton + AJAX data
        // ✅ Contact list (contacts) - HTML skeleton + AJAX data
        // ✅ Sells list (sells) - HTML skeleton + AJAX data
        // ✅ Purchases list (purchases) - HTML skeleton + AJAX data
        // ✅ Reports pages - HTML skeleton + AJAX data
        // ✅ Settings pages
        // ✅ All module pages that use DataTables
        return false;
    }

    /**
     * Check if response should be optimized
     */
    protected function shouldOptimizeResponse($response)
    {
        if ($response->getStatusCode() !== 200) {
            return false;
        }

        $contentType = $response->headers->get('Content-Type', '');

        if (strpos($contentType, 'text/html') === false && empty($contentType)) {
            return false;
        }

        return true;
    }

    /**
     * Check if request is from mobile device
     */
    protected function isMobile(Request $request)
    {
        $userAgent = $request->header('User-Agent');

        $mobileKeywords = [
            'Mobile', 'Android', 'iPhone', 'iPad', 'Windows Phone',
            'BlackBerry', 'Opera Mini', 'IEMobile'
        ];

        foreach ($mobileKeywords as $keyword) {
            if (stripos($userAgent, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if compression should be applied
     */
    protected function shouldCompress(Request $request)
    {
        $acceptEncoding = $request->header('Accept-Encoding', '');

        return strpos($acceptEncoding, 'gzip') !== false;
    }

    /**
     * Record performance statistics
     *
     * @param float $loadTime Load time in milliseconds
     * @param bool $isCacheHit Whether this was a cache hit
     */
    protected function recordPerformance($loadTime, $isCacheHit = false)
    {
        try {
            // Try to get business_id from session first
            $business_id = session()->get('user.business_id');

            // If not in session, try from authenticated user
            if (!$business_id && auth()->check() && auth()->user()) {
                $business_id = auth()->user()->business_id ?? null;
            }

            // If no business_id, use first enabled business
            if (!$business_id) {
                $settings = DB::table('pagespeed_settings')
                    ->where('enabled', 1)
                    ->first();

                if (!$settings) {
                    return;
                }

                $business_id = $settings->business_id;
            } else {
                $settings = DB::table('pagespeed_settings')
                    ->where('business_id', $business_id)
                    ->first();

                if (!$settings) {
                    return;
                }
            }

            $total_requests = $settings->total_requests + 1;
            $new_avg = (($settings->avg_load_time * $settings->total_requests) + $loadTime) / $total_requests;

            $updateData = [
                'total_requests' => $total_requests,
                'avg_load_time' => $new_avg,
            ];

            // Increment cache_hits if this was a cache hit
            if ($isCacheHit) {
                $updateData['cache_hits'] = $settings->cache_hits + 1;
            }

            DB::table('pagespeed_settings')
                ->where('business_id', $business_id)
                ->update($updateData);

        } catch (\Exception $e) {
            // Silently fail to not break the request
        }
    }

    /**
     * Apply Gzip compression to content
     *
     * يضغط المحتوى باستخدام Gzip إذا كان المتصفح يدعمه
     * يقلل حجم الصفحة بنسبة 70-80% تقريباً
     * آمن 100% ولا يؤثر على الوظائف
     *
     * @return array ['content' => string, 'compressed' => bool]
     */
    protected function applyGzipCompression(Request $request, $content)
    {
        // Check if browser supports gzip
        $acceptEncoding = $request->header('Accept-Encoding', '');

        if (strpos($acceptEncoding, 'gzip') === false) {
            // Browser doesn't support gzip, return original content
            return [
                'content' => $content,
                'compressed' => false
            ];
        }

        // Check if content is already compressed
        if (strlen($content) > 2 && substr($content, 0, 2) === "\x1f\x8b") {
            // Already gzipped
            return [
                'content' => $content,
                'compressed' => true
            ];
        }

        // Compress the content
        // Level 6 is good balance between speed and compression
        $compressed = gzencode($content, 6);

        if ($compressed === false) {
            // Compression failed, return original
            return [
                'content' => $content,
                'compressed' => false
            ];
        }

        // Only use compressed version if it's actually smaller
        if (strlen($compressed) < strlen($content)) {
            return [
                'content' => $compressed,
                'compressed' => true
            ];
        }

        // Compressed version is not smaller, use original
        return [
            'content' => $content,
            'compressed' => false
        ];
    }

    /**
     * Add browser caching headers
     *
     * يضيف headers للتحكم في cache المتصفح
     * يجعل المتصفح يحفظ الصفحة لفترة محددة
     * يقلل عدد الطلبات للخادم بشكل كبير
     */
    protected function addBrowserCachingHeaders($response)
    {
        // Cache-Control header
        // max-age=300 means browser will cache for 5 minutes
        $response->header('Cache-Control', 'public, max-age=300, must-revalidate');

        // ETag for validation
        $content = $response->getContent();
        $etag = md5($content);
        $response->header('ETag', '"' . $etag . '"');

        // Last-Modified
        $response->header('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');

        // Vary header - important for proper caching
        $response->header('Vary', 'Accept-Encoding');
    }
}
