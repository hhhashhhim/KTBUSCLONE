<?php

use App\Http\Controllers\CompanyController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'web/v1/company','middleware' => ['auth:sanctum']], function () {
    Route::post('/', [CompanyController::class, 'index']);
    Route::post('store', [CompanyController::class, 'store']);
    Route::post('logo-upload', [CompanyController::class, 'logoUpload']);
    Route::post('update', [CompanyController::class, 'update']);
    Route::post('delete', [CompanyController::class, 'delete']);
    Route::post('/get', [CompanyController::class, 'role']);
    Route::post('/roles', [CompanyController::class, 'company_roles']);
    Route::post('/get', [CompanyController::class, 'company']);
});
