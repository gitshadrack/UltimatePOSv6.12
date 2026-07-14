<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for the Damage Management module.
| These routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group.
|
*/

Route::middleware('web', 'authh', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu')->group(function () {
    
    // Installation Routes
    Route::prefix('damage-management')->group(function () {
        Route::get('/install', [Modules\DamageManagement\Http\Controllers\InstallController::class, 'index']);
        Route::get('/install/update', [Modules\DamageManagement\Http\Controllers\InstallController::class, 'update']);
        Route::get('/install/uninstall', [Modules\DamageManagement\Http\Controllers\InstallController::class, 'uninstall']);
    });
    
    // Damage Records Routes
    Route::get('damage-records/dashboard', 'DamageRecordController@dashboard')->name('damage-records.dashboard');
    Route::get('damage-records/stock-report', 'DamageRecordController@stockReport')->name('damage-records.stock-report');
    Route::get('damage-records/variation-search', 'DamageRecordController@searchVariations')->name('damage-records.variation-search');
    Route::get('damage-records/variation/{id}', 'DamageRecordController@getVariationDetails')->name('damage-records.variation-details');
    Route::get('damage-records/contacts', 'DamageRecordController@searchContacts')->name('damage-records.contacts');
    Route::post('damage-records/{record}/update-status', 'DamageRecordController@updateStatus')->name('damage-records.update-status');
    Route::post('damage-records/{record}/partial-dispatch', 'DamageRecordController@partialDispatch')->name('damage-records.partial-dispatch');
    Route::post('damage-records/{record}/update-approval-status', 'DamageRecordController@updateApprovalStatus')->name('damage-records.update-approval-status');
    Route::post('damage-records/{record}/approve', 'DamageRecordController@approveRecord')->name('damage-records.approve');
    Route::post('damage-records/{record}/reject', 'DamageRecordController@rejectRecord')->name('damage-records.reject');
    Route::get('damage-records/{record}/print', 'DamageRecordController@print')->name('damage-records.print');
    Route::resource('damage-records', 'DamageRecordController');

    // Damage Dispatches Routes
    Route::get('damage-dispatches/available-records', 'DamageDispatchController@availableRecords')->name('damage-dispatches.available-records');
    Route::get('damage-dispatches/{dispatch}/print', 'DamageDispatchController@print')->name('damage-dispatches.print');
    Route::resource('damage-dispatches', 'DamageDispatchController')->except(['edit', 'update']);
});

