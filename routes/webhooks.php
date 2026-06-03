<?php

use App\Http\Controllers\IntaSendController;
use Illuminate\Support\Facades\Route;

Route::match(['get', 'post'], '/intasend/webhook', [IntaSendController::class, 'webhook'])->name('intasend.webhook');
