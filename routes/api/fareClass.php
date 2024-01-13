<?php

use App\Http\Controllers\FareClass\FareClassController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1/fare-class', [CustomMiddleware::class]], function () {
    Route::post('/', [FareClassController::class, 'index']);
    Route::post('/store', [FareClassController::class, 'storeFareClass']);
    Route::post('/update', [FareClassController::class, 'updateFareClass']);
    Route::post('/delete', [FareClassController::class, 'deleteFareClass']);
});
