<?php

use App\Http\Controllers\IntaSendController;
use App\Http\Controllers\DarajaController;
use Illuminate\Support\Facades\Route;

Route::match(['get', 'post'], '/intasend/webhook', [IntaSendController::class, 'webhook'])->name('intasend.webhook');
Route::post('/daraja/stk/{setting}', [DarajaController::class, 'callback'])->name('daraja.callback');
Route::post('/daraja/c2b/{setting}/validation', [DarajaController::class, 'c2bValidation'])->name('daraja.c2b_validation');
Route::post('/daraja/c2b/{setting}/confirmation', [DarajaController::class, 'c2bConfirmation'])->name('daraja.c2b_confirmation');
