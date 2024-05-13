<?php

use App\Http\Controllers\Surcharge\SurchargeController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'web/v1/surcharge','middleware' => ['auth:sanctum']], function () {
    Route::post('/', [SurchargeController::class, 'index']);
    Route::post('/store', [SurchargeController::class, 'storeSurcharge']);
    Route::post('/update', [SurchargeController::class, 'updateSurcharge']);
    // Route::post('/delete', [SurchargeController::class, 'deleteSurcharge']);
});
