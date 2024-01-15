<?php

use App\Http\Controllers\ReportsHeader\ReportsHeaderController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'web/v1/reportsHeader','middleware' => ['auth:sanctum']], function () {
    Route::post('/', [ReportsHeaderController::class, 'index']);
    Route::post('/store', [ReportsHeaderController::class, 'store']);
    Route::post('/update', [ReportsHeaderController::class, 'update']);
    Route::post('/link', [ReportsHeaderController::class, 'headerLink']);
    Route::post('/link/get', [ReportsHeaderController::class, 'linkGet']);
});
