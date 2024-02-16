<?php

use App\Http\Controllers\FareTableController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'web/v1/fare-table','middleware' => ['auth:sanctum']], function () {
    Route::post('/', [FareTableController::class, 'record']);
    Route::post('/store', [FareTableController::class, 'store']);
    Route::post('/fare_class/get', [FareTableController::class, 'getFareClass']);
    Route::post('/cities/classes', [FareTableController::class, 'getCitiesClasses']);
    Route::post('/check', [FareTableController::class, 'check']);
    Route::post('/fare/update', [FareTableController::class, 'fareUpdate']);
    Route::post('/fare/update/multiple', [FareTableController::class, 'fareUpdateMultiple']);
    Route::post('/schedules/times/update', [FareTableController::class, 'updateScheduleTimes']);
    
    // Route::post('/schedules/times/update/progress', [FareTableController::class, 'updateScheduleTimesProgress']);
});

Route::group(['prefix' => 'web/v1/fare-table','middleware' =>  ['custom.sanctum.token.verify']], function () {
    Route::post('/fare/print', [FareTableController::class, 'farePrint']);
});