<?php

use App\Http\Controllers\Account\AccountController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1/accounts', [CustomMiddleware::class]], function () {
    Route::post('/coa/categories', [AccountController::class, 'accountCategories']);
    Route::post('/coa/getSecondLevel', [AccountController::class, 'getSecondLevel']);
    Route::post('/coa/category/store', [AccountController::class, 'categoryStore']);
    Route::post('/coa/category/update', [AccountController::class, 'categoryUpdate']);
});
