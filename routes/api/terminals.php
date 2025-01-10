<?php

use App\Http\Controllers\TerminalController;
use App\Http\Middleware\CustomMiddleware;
use App\Http\Controllers\Report\AdvanceSalesReportController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'web/v1/terminals','middleware' => ['auth:sanctum']], function () {
    Route::post('/dashboard/data', [TerminalController::class, 'dashboardData']);

    Route::post('/', [TerminalController::class, 'index']);
    Route::post('/company', [TerminalController::class, 'companies']);
    Route::post('/cities', [TerminalController::class, 'cities']);
    Route::post('/all', [TerminalController::class, 'allTerminals']);
    Route::post('/getTerminal', [TerminalController::class, 'getTerminal']);
    Route::post('store', [TerminalController::class, 'store']);
    Route::post('update', [TerminalController::class, 'update']);
    Route::post('hide', [TerminalController::class, 'hideTerminal']);
    Route::post('permissions', [TerminalController::class, 'permissions']);
    Route::post('/sales/fetchFilterData', [TerminalController::class, 'filterData']);
    Route::post('/discount/fetchFilterData', [TerminalController::class, 'filterDataDiscount']);

    Route::post('/routes', [TerminalController::class, 'getRoutes']);

    Route::group(['prefix' => '/commissions', [CustomMiddleware::class]], function () {
        Route::post('/', [TerminalController::class, 'terminalCommissions']);
        Route::post('/store', [TerminalController::class, 'commissionStore']);
    });

    Route::group(['prefix' => '/discounts', [CustomMiddleware::class]], function () {
        Route::post('/', [TerminalController::class, 'terminalDiscounts']);
        Route::post('/store', [TerminalController::class, 'discountStore']);
    });
    
    Route::group(['prefix' => '/times', [CustomMiddleware::class]], function () {
        Route::post('/', [TerminalController::class, 'terminalTimes']);
        Route::post('/store', [TerminalController::class, 'timeStore']);
    });
});
