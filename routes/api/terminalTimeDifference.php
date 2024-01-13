<?php

use App\Http\Controllers\Terminal\TerminalTimeDifferenceController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1/terminal_time', [CustomMiddleware::class]], function () {
    Route::post('/cities', [TerminalTimeDifferenceController::class, 'index']);
    Route::post('/cities/get', [TerminalTimeDifferenceController::class, 'getTerminals']);
    Route::post('/store', [TerminalTimeDifferenceController::class, 'store']);
    Route::post('/time/check', [TerminalTimeDifferenceController::class, 'check']);
});
