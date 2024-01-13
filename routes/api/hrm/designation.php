<?php

use App\Http\Controllers\Hrm\Designation\DesignationController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1/hrm/designation', [CustomMiddleware::class]], function () {
    Route::post('/', [DesignationController::class, 'index']);
    Route::post('/store', [DesignationController::class, 'store']);
    Route::post('/getTerminal', [DesignationController::class, 'getTerminal']);
    Route::post('/edit', [DesignationController::class, 'edit']);
    Route::post('/update', [DesignationController::class, 'update']);
    Route::post('/delete', [DesignationController::class, 'delete']);
    Route::post('/selective', [DesignationController::class, 'selective']);
});
