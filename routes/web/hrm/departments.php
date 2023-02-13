<?php

use App\Http\Controllers\Hrm\Department\DepartmentController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'hrm/department', [CustomMiddleware::class]], function () {
    Route::post('/', [DepartmentController::class, 'index']);
    Route::post('/store', [DepartmentController::class, 'store']);
    Route::post('/update', [DepartmentController::class, 'update']);
    Route::post('/delete', [DepartmentController::class, 'delete']);
    Route::post('/selective', [DepartmentController::class, 'selective']);
    Route::post('/all/terminals', [DepartmentController::class, 'allTerminals']);
});
