<?php

use App\Http\Controllers\FareTableController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1/fare-table', [CustomMiddleware::class]], function () {
    Route::post('/', [FareTableController::class, 'record']);
    Route::post('/store', [FareTableController::class, 'store']);
    Route::post('/fare_class/get', [FareTableController::class, 'getFareClass']);
    Route::post('/update/cities/get', [FareTableController::class, 'getUpdateCities']);
    Route::post('/check', [FareTableController::class, 'check']);
    Route::post('/fare/update', [FareTableController::class, 'fareUpdate']);
    Route::post('/schedules/times/update', [FareTableController::class, 'updateScheduleTimes']);
    Route::post('/fare/print', [FareTableController::class, 'farePrint']);
    
    Route::post('/schedules/times/update/progress', [FareTableController::class, 'updateScheduleTimesProgress']);
});
