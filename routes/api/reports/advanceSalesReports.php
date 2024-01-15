<?php

use App\Http\Controllers\Report\AdvanceSalesReportController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'web/v1/advance/sales','middleware' => ['auth:sanctum']], function () {
    Route::post('/pdf', [AdvanceSalesReportController::class, 'advanceSalePdf']);
    Route::post('/getTerminals', [AdvanceSalesReportController::class, 'getTerminals']);
    Route::post('/getUserNames', [AdvanceSalesReportController::class, 'getUserNames']);
    Route::post('/getRoutes', [AdvanceSalesReportController::class, 'getRoutes']);
    Route::post('/fetchFilterData', [AdvanceSalesReportController::class, 'filterData']);
});
