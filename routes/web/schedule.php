<?php

use App\Http\Controllers\Schedule\ScheduleController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'schedule', [CustomMiddleware::class]], function () {
    Route::post('/', [ScheduleController::class, 'index']);
    Route::post('/store', [ScheduleController::class, 'storeSchedule']);
//    Route::post('/edit', [ScheduleController::class, 'editSchedule']);
    Route::post('/update', [ScheduleController::class, 'updateSchedule']);
    Route::post('/delete', [ScheduleController::class, 'deleteSchedule']);
    Route::post('/getRoute', [ScheduleController::class, 'getRoutes']);
    Route::post('/getCity', [ScheduleController::class, 'getCity']);
    Route::post('/getEntire', [ScheduleController::class, 'getEntire']);
    Route::post('/getRouteFare', [ScheduleController::class, 'getRouteFareClass']);
    Route::post('/genericCommon', [ScheduleController::class, 'genericCommon']);
    Route::post('/selected', [ScheduleController::class, 'selected']);
    Route::post('/dropCheck', [ScheduleController::class, 'dropCheck']);
    Route::post('/extend', [ScheduleController::class, 'extend']);
//    Route::post('/extendedGet', [ScheduleController::class, 'extendedGet']);
    Route::post('/allBuses', [ScheduleController::class, 'allBuses']);

});
