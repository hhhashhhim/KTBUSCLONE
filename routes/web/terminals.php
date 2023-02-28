<?php

use App\Http\Controllers\TerminalController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'terminals', 'middleware' => 'UserAccess', [CustomMiddleware::class]], function () {
    Route::post('/', [TerminalController::class, 'index']);
    Route::post('/company', [TerminalController::class, 'companies']);
    Route::post('/cities', [TerminalController::class, 'cities']);
    Route::post('/all', [TerminalController::class, 'allTerminals']);
    Route::post('/getTerminal', [TerminalController::class, 'getTerminal']);
    Route::post('store', [TerminalController::class, 'store']);
    Route::post('update', [TerminalController::class, 'update']);
    Route::post('delete', [TerminalController::class, 'delete']);
    Route::post('permissions', [TerminalController::class, 'permissions']);

    Route::post('/routes', [TerminalController::class, 'getRoutes']);

    Route::group(['prefix' => '/commissions', [CustomMiddleware::class]], function () {
        Route::post('/', [TerminalController::class, 'terminalCommissions']);
        Route::post('/store', [TerminalController::class, 'commissionStore']);
    });

    Route::group(['prefix' => '/discounts', [CustomMiddleware::class]], function () {
        Route::post('/', [TerminalController::class, 'terminalDiscounts']);
        Route::post('/store', [TerminalController::class, 'discountStore']);
    });
});
