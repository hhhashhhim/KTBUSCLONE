<?php

use App\Http\Controllers\Hrm\Employee\EmployeeController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'web/v1/hrm/employee','middleware' => ['auth:sanctum']], function () {
    Route::post('/', [EmployeeController::class, 'index']);
    Route::post('/cities', [EmployeeController::class, 'getCities']);
    Route::post('/store', [EmployeeController::class, 'store']);
    Route::post('/update', [EmployeeController::class, 'update']);
    Route::post('/hide', [EmployeeController::class, 'hideEmployee']);
    Route::post('/user/store', [EmployeeController::class, 'userStore']);
});
