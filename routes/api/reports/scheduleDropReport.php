<?php

use App\Http\Controllers\ReportsHeader\ReportsHeaderController;
use App\Http\Controllers\Report\ScheduleDropReportController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'web/v1/report','middleware' => ['auth:sanctum']], function () {
    Route::post('/schedules/drop', [ScheduleDropReportController::class, 'dropReport']);
});
