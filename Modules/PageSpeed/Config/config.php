<?php

return [
    'name' => 'PageSpeed',
    'module_version' => '1.0',
    'pid' => env('PAGESPEED_PID', 'PAGESPEED_MODULE'),

    /*
    |--------------------------------------------------------------------------
    | Performance Optimization Features (Like WP Rocket)
    |--------------------------------------------------------------------------
    */

    // Cache Settings
    'cache' => [
        'enabled' => true,
        'page_cache_lifetime' => 86400, // 24 hours in seconds
        'database_cache_lifetime' => 3600, // 1 hour
        'cache_mobile_separate' => true,
        'cache_logged_in_users' => false,
        'cache_query_strings' => false,
        'cache_prefix' => 'pagespeed_',
    ],

    // HTML Minification
    'minify_html' => [
        'enabled' => true,
        'remove_comments' => true,
        'remove_whitespace' => true,
        'compress_inline_css' => true,
        'compress_inline_js' => true,
    ],

    // CSS Optimization
    'css_optimization' => [
        'minify' => true,
        'combine_files' => true,
        'inline_critical_css' => true,
        'defer_non_critical' => true,
        'remove_unused_css' => false,
    ],

    // JavaScript Optimization
    'js_optimization' => [
        'minify' => true,
        'combine_files' => true,
        'defer_loading' => true,
        'async_loading' => false,
        'delay_execution' => false,
        'exclude_jquery' => true, // Don't defer jQuery
    ],

    // Image Optimization
    'image_optimization' => [
        'lazy_load' => true,
        'lazy_load_iframes' => true,
        'webp_conversion' => false,
        'responsive_images' => true,
        'exclude_first_images' => 3, // Don't lazy load first N images
    ],

    // Database Optimization
    'database' => [
        'cache_queries' => true,
        'optimize_tables' => false,
        'auto_cleanup' => true,
        'cleanup_schedule' => 'weekly',
    ],

    // GZIP Compression
    'compression' => [
        'enabled' => true,
        'level' => 6, // 1-9
        'types' => ['text/html', 'text/css', 'text/javascript', 'application/javascript', 'application/json'],
    ],

    // Browser Caching
    'browser_cache' => [
        'enabled' => true,
        'css_lifetime' => 31536000, // 1 year
        'js_lifetime' => 31536000,
        'images_lifetime' => 31536000,
        'fonts_lifetime' => 31536000,
    ],

    // Preloading & DNS Prefetch
    'preload' => [
        'enabled' => true,
        'fonts' => true,
        'critical_images' => true,
        'dns_prefetch' => true,
        'dns_prefetch_domains' => [
            '//fonts.googleapis.com',
            '//fonts.gstatic.com',
        ],
    ],

    // CDN Integration
    'cdn' => [
        'enabled' => false,
        'cdn_url' => env('CDN_URL', ''),
        'cdn_domains' => [],
        'include_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'css', 'js', 'woff', 'woff2', 'ttf'],
    ],

    // Advanced Optimizations
    'advanced' => [
        'remove_query_strings' => true,
        'disable_emojis' => false,
        'disable_embeds' => false,
        'remove_dashicons' => false,
        'preconnect_google_fonts' => true,
    ],

    // Exclusions
    'exclusions' => [
        'urls' => [
            // URLs to exclude from optimization
            '/login',
            '/register',
            '/checkout',
            '/pos',
            '/admin',
        ],
        'js_files' => [
            // JS files to exclude from optimization
            'recaptcha',
            'google-analytics',
            'gtag',
        ],
        'css_files' => [
            // CSS files to exclude from optimization
        ],
    ],

    // Performance Monitoring
    'monitoring' => [
        'enabled' => true,
        'track_load_time' => true,
        'track_cache_hits' => true,
        'generate_reports' => true,
    ],
];
