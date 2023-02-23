<?php

use App\Http\Controllers\CityController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'cities', [CustomMiddleware::class]], function () {
    Route::post('/', [CityController::class, 'index']);
    Route::post('store', [CityController::class, 'store']);
    Route::post('update', [CityController::class, 'update']);
    Route::post('delete', [CityController::class, 'delete']);
    Route::post('/terminals', [CityController::class, 'cityTerminals']);
});
