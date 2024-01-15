<?php

use App\Http\Controllers\CityController;
use App\Http\Controllers\Expense\ExpenseCategoryController;
use App\Http\Controllers\Expense\ExpenseController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'web/v1/expenses','middleware' => ['auth:sanctum']], function () {
    
    Route::post('/', [ExpenseController::class, 'index']);
    Route::post('store', [ExpenseController::class, 'store']);

    Route::group(['prefix' => '/categories', [CustomMiddleware::class]], function () {
        Route::post('/', [ExpenseCategoryController::class, 'index']);
        Route::post('store', [ExpenseCategoryController::class, 'store']);
        Route::post('update', [ExpenseCategoryController::class, 'update']);
        // Route::post('delete', [CityController::class, 'delete']);
    });
});

Route::group(['prefix' => 'web/v1/office/expenses','middleware' => ['auth:sanctum']], function () {
    
    Route::post('/', [ExpenseController::class, 'officeExpenses']);
    Route::post('store', [ExpenseController::class, 'officeExpenStore']);
    Route::post('update', [ExpenseController::class, 'officeExpenUpdate']);
    
});
