<?php

use App\Http\Controllers\FareTableController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'fare-table', [CustomMiddleware::class]], function () {
    Route::post('/', [FareTableController::class, 'record']);
    Route::post('/store', [FareTableController::class, 'store']);
    Route::post('/fare_class/get', [FareTableController::class, 'getFareClass']);
    Route::post('/check', [FareTableController::class, 'check']);
    Route::post('/schedules/times/update', [FareTableController::class, 'updateScheduleTimes']);
});


// Route::get('/progress/{id}', function ($batchId) {
//     return Bus::findBatch($batchId);
// });