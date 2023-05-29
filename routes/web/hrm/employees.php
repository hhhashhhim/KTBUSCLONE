<?php

use App\Http\Controllers\Hrm\Employee\EmployeeController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'hrm/employee', [CustomMiddleware::class]], function () {
    Route::post('/', [EmployeeController::class, 'index']);
    Route::post('/cities', [EmployeeController::class, 'getCities']);
    Route::post('/store', [EmployeeController::class, 'store']);
    Route::post('/update', [EmployeeController::class, 'update']);
    Route::post('/delete', [EmployeeController::class, 'delete']);
    Route::post('/user/store', [EmployeeController::class, 'userStore']);
});
