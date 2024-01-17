<?php

use App\Http\Controllers\Report\DailySummaryReportController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'web/v1/reports','middleware' => ['auth:sanctum']], function () {
    Route::post('/getRoutes', [DailySummaryReportController::class, 'getRoutes']);
    Route::post('/getBuses', [DailySummaryReportController::class, 'getBuses']);
});

Route::group(['prefix' => 'web/v1/reports','middleware' => ['custom.sanctum.token.verify']], function () {
    Route::post('/reportExport', [DailySummaryReportController::class, 'exportReport']);
});
