<?php

use App\Http\Controllers\Report\TerminalCommissionReportController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'web/v1/terminal/commissions','middleware' => ['auth:sanctum']], function () {
    Route::post('/getTerminals', [TerminalCommissionReportController::class, 'getTerminals']);
    Route::post('/getUserNames', [TerminalCommissionReportController::class, 'getUserNames']);
    Route::post('/getRoutes', [TerminalCommissionReportController::class, 'getRoutes']);
    Route::post('/fetchFilterData', [TerminalCommissionReportController::class, 'filterData']);
});

Route::group(['prefix' => 'web/v1/terminal/commissions','middleware' => ['custom.sanctum.token.verify']], function () {
    Route::post('/pdf', [TerminalCommissionReportController::class, 'advanceSalePdf']);
});
