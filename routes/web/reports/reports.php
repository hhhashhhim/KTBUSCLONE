<?php

use App\Http\Controllers\Report\DailySummaryReportController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'reports', [CustomMiddleware::class]], function () {
    Route::post('/getSchedule', [DailySummaryReportController::class, 'getSchedule']);
    Route::post('/getBuses', [DailySummaryReportController::class, 'getBuses']);
    Route::post('/reportExport', [DailySummaryReportController::class, 'exportReport']);
});
