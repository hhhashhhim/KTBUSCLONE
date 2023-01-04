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
    Route::post('/routes', [CityController::class, 'cityRoutes']);
    Route::post('/routes/update', [CityController::class, 'cityRoutesUpdate']);
    Route::post('/routes/list', [CityController::class, 'city_routes_list']);
    Route::post('/routes/details', [CityController::class, 'city_routes_details']);
});
