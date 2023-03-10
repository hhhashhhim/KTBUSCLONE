<?php

use App\Http\Controllers\Card\CardCategoryController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'loyaltyCard', [CustomMiddleware::class]], function () {
    Route::post('/', [CardCategoryController::class, 'index']);
    Route::post('/store', [CardCategoryController::class, 'store']);
    Route::post('/update', [CardCategoryController::class, 'update']);
});
