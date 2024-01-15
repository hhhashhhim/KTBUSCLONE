<?php

use App\Http\Controllers\CityController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'web/v1/cities','middleware' => ['auth:sanctum']], function () {
    Route::post('/', [CityController::class, 'index']);
    Route::post('store', [CityController::class, 'store']);
    Route::post('update', [CityController::class, 'update']);
    Route::post('hide', [CityController::class, 'hideCity']);
    Route::post('/terminals', [CityController::class, 'cityTerminals']);
});
