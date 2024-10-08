<?php


use App\Http\Controllers\Report\ConfirmCancellationReportController;
use App\Http\Controllers\Report\OverissueReportController;
use App\Http\Controllers\Report\RescheduleReportController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'web/v1/confirm/cancellation','middleware' => ['auth:sanctum']], function () {
    Route::post('/getTerminals', [ConfirmCancellationReportController::class, 'getTerminals']);
    Route::post('/fetchFilterData', [ConfirmCancellationReportController::class, 'filterData']);
});

Route::group(['prefix' => 'web/v1/over-issue','middleware' => ['auth:sanctum']], function () {
    Route::post('/getTerminals', [OverissueReportController::class, 'getTerminals']);
    Route::post('/fetchFilterData', [OverissueReportController::class, 'filterData']);
});

Route::group(['prefix' => 'web/v1/reschedule','middleware' => ['auth:sanctum']], function () {
    Route::post('/getTerminals', [RescheduleReportController::class, 'getTerminals']);
    Route::post('/fetchFilterData', [RescheduleReportController::class, 'filterData']);
});
