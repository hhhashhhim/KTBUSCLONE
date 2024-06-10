<?php

use App\Http\Controllers\Schedule\ScheduleController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'web/v1/schedule','middleware' => ['auth:sanctum']], function () {
    Route::post('/', [ScheduleController::class, 'index']);
    Route::post('/store', [ScheduleController::class, 'storeSchedule']);
    Route::post('/edit', [ScheduleController::class, 'editSchedule']);
    Route::post('/update', [ScheduleController::class, 'updateSchedule']);
    Route::post('/time/update', [ScheduleController::class, 'updateScheduleTime']);
    Route::post('/hide', [ScheduleController::class, 'hideSchedule']);
    Route::post('/getRoute', [ScheduleController::class, 'getRoutes']);
    Route::post('/getTerminals', [ScheduleController::class, 'getTerminals']);
    Route::post('/getCity', [ScheduleController::class, 'getCity']);
    Route::post('/getEntire', [ScheduleController::class, 'getEntire']);
    Route::post('/getRouteFare', [ScheduleController::class, 'getRouteFareClass']);
    Route::post('/genericCommon', [ScheduleController::class, 'genericCommon']);
    Route::post('/extend', [ScheduleController::class, 'extend']);
    Route::post('/allBuses', [ScheduleController::class, 'allBuses']);
    Route::post('/fare-class', [ScheduleController::class, 'fareClasses']);
    Route::post('/bus_classes', [ScheduleController::class, 'busClasses']);
    Route::post('/surcharge/getSelective', [ScheduleController::class, 'surchargeSelective']);
    Route::post('/discount/getSelective', [ScheduleController::class, 'discountSelective']);
});
