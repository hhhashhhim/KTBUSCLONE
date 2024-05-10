<?php

use App\Http\Controllers\Bus\BusController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'web/v1/buses','middleware' => ['auth:sanctum']], function () {
    Route::post('/', [BusController::class, 'index']);
    Route::post('/store', [BusController::class, 'storeBus']);
    Route::post('/update', [BusController::class, 'updateBus']);
    // Route::post('/delete', [BusController::class, 'deleteBus']);
    Route::post('/getBusData', [BusController::class, 'getBusData']);
    Route::post('/single/schedule/latest', [BusController::class, 'getBusSchedule']);
    Route::post('/bus_classes', [BusController::class, 'busClasses']);

});
