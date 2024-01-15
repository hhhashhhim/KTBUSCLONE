<?php

use App\Http\Controllers\Terminal\TerminalTimeDifferenceController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'web/v1/terminal_time','middleware' => ['auth:sanctum']], function () {
    Route::post('/cities', [TerminalTimeDifferenceController::class, 'index']);
    Route::post('/cities/get', [TerminalTimeDifferenceController::class, 'getTerminals']);
    Route::post('/store', [TerminalTimeDifferenceController::class, 'store']);
    Route::post('/time/check', [TerminalTimeDifferenceController::class, 'check']);
});
