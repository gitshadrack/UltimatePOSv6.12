<?php

use Illuminate\Support\Facades\Route;

Route::middleware('web', 'authh', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu', 'CheckUserLogin')->prefix('pagespeed')->group(function () {
    // Installation routes
    Route::get('install', [Modules\PageSpeed\Http\Controllers\InstallController::class, 'index']);
    Route::post('install', [Modules\PageSpeed\Http\Controllers\InstallController::class, 'index']);
    Route::get('install/uninstall', [Modules\PageSpeed\Http\Controllers\InstallController::class, 'uninstall']);
    Route::get('install/update', [Modules\PageSpeed\Http\Controllers\InstallController::class, 'update']);

    // Settings routes
    Route::get('/', [Modules\PageSpeed\Http\Controllers\PageSpeedController::class, 'index'])->name('pagespeed.index');
    Route::post('/settings', [Modules\PageSpeed\Http\Controllers\PageSpeedController::class, 'updateSettings'])->name('pagespeed.settings.update');

    // Cache management
    Route::post('/cache/clear', [Modules\PageSpeed\Http\Controllers\PageSpeedController::class, 'clearCache'])->name('pagespeed.cache.clear');

    // Statistics
    Route::get('/statistics', [Modules\PageSpeed\Http\Controllers\PageSpeedController::class, 'getStatistics'])->name('pagespeed.statistics');
});
