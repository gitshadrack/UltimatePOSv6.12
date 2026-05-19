<?php

return [
    // Module Info
    'pagespeed' => 'PageSpeed',
    'pagespeed_module' => 'PageSpeed Optimization Module',
    'page_speed_optimization' => 'Page Speed Optimization',

    // Permissions
    'access_pagespeed' => 'Access PageSpeed',
    'manage_settings' => 'Manage PageSpeed Settings',
    'clear_cache' => 'Clear PageSpeed Cache',
    'view_statistics' => 'View PageSpeed Statistics',

    // Settings Page
    'settings' => 'Settings',
    'general_settings' => 'General Settings',
    'cache_settings' => 'Cache Settings',
    'optimization_settings' => 'Optimization Settings',
    'advanced_settings' => 'Advanced Settings',

    // General
    'enable_optimization' => 'Enable Optimization',
    'enable_optimization_help' => 'Enable or disable all PageSpeed optimizations',

    // Cache Settings
    'page_cache' => 'Page Cache',
    'enable_page_cache' => 'Enable Page Caching',
    'page_cache_lifetime' => 'Page Cache Lifetime (seconds)',
    'database_cache' => 'Database Cache',
    'enable_database_cache' => 'Enable Database Query Caching',
    'cache_mobile_separate' => 'Cache Mobile Separately',
    'cache_logged_in_users' => 'Cache for Logged-in Users',

    // HTML Optimization
    'html_optimization' => 'HTML Optimization',
    'minify_html' => 'Minify HTML',
    'remove_html_comments' => 'Remove HTML Comments',

    // CSS Optimization
    'css_optimization' => 'CSS Optimization',
    'minify_css' => 'Minify CSS',
    'combine_css' => 'Combine CSS Files',
    'inline_critical_css' => 'Inline Critical CSS',

    // JavaScript Optimization
    'js_optimization' => 'JavaScript Optimization',
    'minify_js' => 'Minify JavaScript',
    'combine_js' => 'Combine JavaScript Files',
    'defer_js' => 'Defer JavaScript Loading',

    // Image Optimization
    'image_optimization' => 'Image Optimization',
    'lazy_load_images' => 'Lazy Load Images',
    'lazy_load_iframes' => 'Lazy Load Iframes/Videos',
    'exclude_first_images' => 'Exclude First N Images',
    'exclude_first_images_help' => 'Number of images to exclude from lazy loading (for above-the-fold content)',

    // Compression
    'compression' => 'Compression',
    'enable_gzip' => 'Enable GZIP Compression',
    'gzip_level' => 'GZIP Compression Level (1-9)',

    // Browser Caching
    'browser_caching' => 'Browser Caching',
    'enable_browser_cache' => 'Enable Browser Caching',

    // Preloading
    'preloading' => 'Preloading',
    'enable_preload' => 'Enable Resource Preloading',
    'enable_dns_prefetch' => 'Enable DNS Prefetch',

    // CDN
    'cdn' => 'CDN Integration',
    'enable_cdn' => 'Enable CDN',
    'cdn_url' => 'CDN URL',

    // Advanced
    'advanced' => 'Advanced Options',
    'remove_query_strings' => 'Remove Query Strings from Static Resources',
    'disable_emojis' => 'Disable WordPress Emojis',

    // Statistics
    'statistics' => 'Performance Statistics',
    'cache_hits' => 'Cache Hits',
    'total_requests' => 'Total Requests',
    'avg_load_time' => 'Average Load Time (ms)',
    'cache_count' => 'Cached Pages',
    'cache_size' => 'Cache Size (MB)',
    'hit_ratio' => 'Cache Hit Ratio',

    // Actions
    'clear_all_cache' => 'Clear All Cache',
    'save_settings' => 'Save Settings',
    'refresh_statistics' => 'Refresh Statistics',

    // Messages
    'settings_updated' => 'PageSpeed settings updated successfully!',
    'cache_cleared' => 'All cache cleared successfully!',
    'optimization_active' => 'PageSpeed optimization is active',
    'optimization_inactive' => 'PageSpeed optimization is disabled',

    // Help Text
    'help_minify_html' => 'Removes unnecessary whitespace and comments from HTML output',
    'help_lazy_load' => 'Delays loading of images until they are visible in the viewport',
    'help_defer_js' => 'Defers JavaScript execution until the page has finished loading',
    'help_gzip' => 'Compresses pages before sending to the browser',
    'help_cache' => 'Stores fully rendered pages to serve them faster on subsequent requests',

    // Warnings
    'warning_combine_files' => 'Warning: Combining files may cause issues with some themes/plugins',
    'warning_minify' => 'Warning: Minification may break JavaScript functionality. Test thoroughly!',
    'warning_cache_logged_in' => 'Warning: Caching logged-in users may show personalized content to others',
];
