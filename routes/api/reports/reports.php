<?php

use App\Http\Controllers\Report\DailySummaryReportController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'web/v1/reports', [CustomMiddleware::class]], function () {
    Route::post('/getRoutes', [DailySummaryReportController::class, 'getRoutes']);
    Route::post('/getBuses', [DailySummaryReportController::class, 'getBuses']);
    Route::post('/reportExport', [DailySummaryReportController::class, 'exportReport']);
});
