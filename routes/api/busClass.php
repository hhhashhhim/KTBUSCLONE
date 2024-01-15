<?php

use App\Http\Controllers\Bus\BusClassController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'web/v1/bus_classes','middleware' => ['auth:sanctum']], function () {
    Route::post('/', [BusClassController::class, 'index']);
    Route::post('/store', [BusClassController::class, 'storeBusClass']);
    Route::post('/update', [BusClassController::class, 'updateBusClass']);
    Route::post('/hide', [BusClassController::class, 'hideBusClass']);
    Route::post('/duplicate', [BusClassController::class, 'duplicateBusClass']);
    Route::post('/fare-class', [BusClassController::class, 'fareClasses']);
    Route::post('/storeFareClass', [BusClassController::class, 'saveFareClass']);
});
