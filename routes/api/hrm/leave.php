<?php

use App\Http\Controllers\Hrm\Leave\LeaveController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1/hrm/leave', [CustomMiddleware::class]], function () {
    Route::post('/', [LeaveController::class, 'index']);
    Route::post('/store', [LeaveController::class, 'store']);
    Route::post('/update', [LeaveController::class, 'update']);
    Route::post('/delete', [LeaveController::class, 'delete']);
    Route::post('/approval', [LeaveController::class, 'approval']);
});
