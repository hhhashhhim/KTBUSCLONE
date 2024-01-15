<?php

use App\Http\Controllers\Card\CardCategoryController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'web/v1/loyaltyCard','middleware' => ['auth:sanctum']], function () {
    Route::post('/', [CardCategoryController::class, 'index']);
    Route::post('/store', [CardCategoryController::class, 'store']);
    Route::post('/update', [CardCategoryController::class, 'update']);
});
