<?php

use App\Http\Controllers\CityController;
use App\Http\Controllers\Expense\ExpenseCategoryController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'expenses', [CustomMiddleware::class]], function () {
    
    Route::group(['prefix' => '/categories', [CustomMiddleware::class]], function () {
        Route::post('/', [ExpenseCategoryController::class, 'index']);
        Route::post('store', [ExpenseCategoryController::class, 'store']);
        Route::post('update', [ExpenseCategoryController::class, 'update']);
        // Route::post('delete', [CityController::class, 'delete']);
    });
});
