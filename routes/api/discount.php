<?php

use App\Http\Controllers\Discount\DiscountController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1/discount', [CustomMiddleware::class]], function () {
    Route::post('/', [DiscountController::class, 'index']);
    Route::post('/store', [DiscountController::class, 'storeDiscount']);
    Route::post('/update', [DiscountController::class, 'updateDiscount']);
    Route::post('/delete', [DiscountController::class, 'deleteDiscount']);
});
