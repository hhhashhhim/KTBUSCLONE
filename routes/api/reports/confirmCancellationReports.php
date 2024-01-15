<?php


use App\Http\Controllers\Report\ConfirmCancellationReportController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'web/v1/confirm/cancellation','middleware' => ['auth:sanctum']], function () {
    Route::post('/getTerminals', [ConfirmCancellationReportController::class, 'getTerminals']);
    Route::post('/fetchFilterData', [ConfirmCancellationReportController::class, 'filterData']);
});
