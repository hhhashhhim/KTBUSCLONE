<?php

use App\Http\Controllers\Card\CardCategoryController;
use App\Http\Controllers\DiscountType\DiscountTypeController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'web/v1/loyaltyCard','middleware' => ['auth:sanctum']], function () {
    Route::post('/', [CardCategoryController::class, 'index']);
    Route::post('/store', [CardCategoryController::class, 'store']);
    Route::post('/update', [CardCategoryController::class, 'update']);
});
Route::group(['prefix' => 'web/v1/discountCard','middleware' => ['auth:sanctum']], function () {
    Route::post('/', [DiscountTypeController::class, 'index']);
    Route::post('/store', [DiscountTypeController::class, 'store']);
    Route::post('/update', [DiscountTypeController::class, 'update']);
});
