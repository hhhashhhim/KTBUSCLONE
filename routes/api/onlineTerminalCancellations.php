<?php

use App\Http\Controllers\Api\OnlineTerminalCancellationController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'web/v1/online-terminals/cancellations', 'middleware' => ['auth:sanctum']], function () {
    Route::post('/requests', [OnlineTerminalCancellationController::class, 'requests']);
    Route::post('/routes', [OnlineTerminalCancellationController::class, 'routes']);
    Route::post('/terminals', [OnlineTerminalCancellationController::class, 'terminals']);
    Route::post('/buses', [OnlineTerminalCancellationController::class, 'buses']);
    Route::post('/approve', [OnlineTerminalCancellationController::class, 'approveRequest']);
    Route::post('/reject', [OnlineTerminalCancellationController::class, 'rejectRequest']);
});
