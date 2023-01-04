<?php

use App\Http\Controllers\Bus\BusController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'buses', [CustomMiddleware::class]], function () {
    Route::post('/', [BusController::class, 'index']);
    Route::post('/store', [BusController::class, 'storeBus']);
    Route::post('/update', [BusController::class, 'updateBus']);
    Route::post('/delete', [BusController::class, 'deleteBus']);
    Route::post('/getBusData', [BusController::class, 'getBusData']);
    Route::post('/storeFareClass', [BusController::class, 'saveFareClass']);

    Route::post('/single/schedule/latest', [BusController::class, 'getBusSchedule']);

});
