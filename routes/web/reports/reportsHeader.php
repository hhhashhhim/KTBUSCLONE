<?php

use App\Http\Controllers\ReportsHeader\ReportsHeaderController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'reportsHeader', [CustomMiddleware::class]], function () {
    Route::post('/', [ReportsHeaderController::class, 'index']);
    Route::post('/store', [ReportsHeaderController::class, 'store']);
    Route::post('/update', [ReportsHeaderController::class, 'update']);
});
