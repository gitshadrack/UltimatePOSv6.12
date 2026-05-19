<?php

return [
    // Module Info
    'pagespeed' => 'PageSpeed',
    'pagespeed_module' => 'وحدة تحسين سرعة الصفحات',
    'page_speed_optimization' => 'تحسين سرعة الصفحات',

    // Permissions
    'access_pagespeed' => 'الوصول إلى PageSpeed',
    'manage_settings' => 'إدارة إعدادات PageSpeed',
    'clear_cache' => 'مسح ذاكرة PageSpeed المؤقتة',
    'view_statistics' => 'عرض إحصائيات PageSpeed',

    // Settings Page
    'settings' => 'الإعدادات',
    'general_settings' => 'الإعدادات العامة',
    'cache_settings' => 'إعدادات ذاكرة التخزين المؤقت',
    'optimization_settings' => 'إعدادات التحسين',
    'advanced_settings' => 'الإعدادات المتقدمة',

    // General
    'enable_optimization' => 'تفعيل التحسين',
    'enable_optimization_help' => 'تفعيل أو تعطيل جميع تحسينات PageSpeed',

    // Cache Settings
    'page_cache' => 'التخزين المؤقت للصفحات',
    'enable_page_cache' => 'تفعيل التخزين المؤقت للصفحات',
    'page_cache_lifetime' => 'مدة التخزين المؤقت للصفحات (بالثواني)',
    'database_cache' => 'التخزين المؤقت لقاعدة البيانات',
    'enable_database_cache' => 'تفعيل التخزين المؤقت لاستعلامات قاعدة البيانات',
    'cache_mobile_separate' => 'تخزين الجوال بشكل منفصل',
    'cache_logged_in_users' => 'التخزين المؤقت للمستخدمين المسجلين',

    // HTML Optimization
    'html_optimization' => 'تحسين HTML',
    'minify_html' => 'تصغير HTML',
    'remove_html_comments' => 'إزالة تعليقات HTML',

    // CSS Optimization
    'css_optimization' => 'تحسين CSS',
    'minify_css' => 'تصغير CSS',
    'combine_css' => 'دمج ملفات CSS',
    'inline_critical_css' => 'إدراج CSS الحرجة',

    // JavaScript Optimization
    'js_optimization' => 'تحسين JavaScript',
    'minify_js' => 'تصغير JavaScript',
    'combine_js' => 'دمج ملفات JavaScript',
    'defer_js' => 'تأجيل تحميل JavaScript',

    // Image Optimization
    'image_optimization' => 'تحسين الصور',
    'lazy_load_images' => 'التحميل الكسول للصور',
    'lazy_load_iframes' => 'التحميل الكسول للإطارات/الفيديو',
    'exclude_first_images' => 'استثناء أول N من الصور',
    'exclude_first_images_help' => 'عدد الصور المستثناة من التحميل الكسول (للمحتوى المرئي مباشرة)',

    // Compression
    'compression' => 'الضغط',
    'enable_gzip' => 'تفعيل ضغط GZIP',
    'gzip_level' => 'مستوى ضغط GZIP (1-9)',

    // Browser Caching
    'browser_caching' => 'التخزين المؤقت للمتصفح',
    'enable_browser_cache' => 'تفعيل التخزين المؤقت للمتصفح',

    // Preloading
    'preloading' => 'التحميل المسبق',
    'enable_preload' => 'تفعيل التحميل المسبق للموارد',
    'enable_dns_prefetch' => 'تفعيل جلب DNS المسبق',

    // CDN
    'cdn' => 'تكامل CDN',
    'enable_cdn' => 'تفعيل CDN',
    'cdn_url' => 'رابط CDN',

    // Advanced
    'advanced' => 'خيارات متقدمة',
    'remove_query_strings' => 'إزالة معاملات الاستعلام من الموارد الثابتة',
    'disable_emojis' => 'تعطيل رموز WordPress التعبيرية',

    // Statistics
    'statistics' => 'إحصائيات الأداء',
    'cache_hits' => 'نجاحات ذاكرة التخزين المؤقت',
    'total_requests' => 'إجمالي الطلبات',
    'avg_load_time' => 'متوسط وقت التحميل (مللي ثانية)',
    'cache_count' => 'الصفحات المخزنة مؤقتًا',
    'cache_size' => 'حجم ذاكرة التخزين المؤقت (ميجابايت)',
    'hit_ratio' => 'نسبة نجاح ذاكرة التخزين المؤقت',

    // Actions
    'clear_all_cache' => 'مسح جميع ذاكرة التخزين المؤقت',
    'save_settings' => 'حفظ الإعدادات',
    'refresh_statistics' => 'تحديث الإحصائيات',

    // Messages
    'settings_updated' => 'تم تحديث إعدادات PageSpeed بنجاح!',
    'cache_cleared' => 'تم مسح جميع ذاكرة التخزين المؤقت بنجاح!',
    'optimization_active' => 'تحسين PageSpeed نشط',
    'optimization_inactive' => 'تحسين PageSpeed معطل',

    // Help Text
    'help_minify_html' => 'يزيل المسافات والتعليقات غير الضرورية من مخرجات HTML',
    'help_lazy_load' => 'يؤجل تحميل الصور حتى تصبح مرئية في منطقة العرض',
    'help_defer_js' => 'يؤجل تنفيذ JavaScript حتى انتهاء تحميل الصفحة',
    'help_gzip' => 'يضغط الصفحات قبل إرسالها إلى المتصفح',
    'help_cache' => 'يخزن الصفحات المعروضة بالكامل لتقديمها بشكل أسرع في الطلبات اللاحقة',

    // Warnings
    'warning_combine_files' => 'تحذير: دمج الملفات قد يسبب مشاكل مع بعض القوالب/الإضافات',
    'warning_minify' => 'تحذير: التصغير قد يكسر وظائف JavaScript. اختبر بدقة!',
    'warning_cache_logged_in' => 'تحذير: التخزين المؤقت للمستخدمين المسجلين قد يعرض محتوى شخصي للآخرين',
];
