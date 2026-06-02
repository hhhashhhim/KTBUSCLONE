<?php

use App\Http\Controllers\Card\CardAssignController;
use App\Http\Controllers\DiscountType\DiscountCardAssignController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'web/v1/loyaltyCardAssign','middleware' => ['auth:sanctum']], function () {
    Route::post('/', [CardAssignController::class, 'index']);
    Route::post('/store', [CardAssignController::class, 'store']);
    Route::post('/categories', [CardAssignController::class, 'cardCategories']);
    Route::post('/update', [CardAssignController::class, 'update']);
    Route::post('/getCNIC', [CardAssignController::class, 'getCNIC']);

});
Route::get('web/v1/loyalty-card/customer/{customer_id}/discount-history', [CardAssignController::class, 'discountHistory'])
    ->middleware('auth:sanctum');
Route::get('web/v1/loyalty-card/customer/{customer_id}/card-history', [CardAssignController::class, 'cardHistory'])
    ->middleware('auth:sanctum');
Route::group(['prefix' => 'web/v1/discountCardAssign','middleware' => ['auth:sanctum']], function () {
    Route::post('/', [DiscountCardAssignController::class, 'index']);
    Route::post('/store', [DiscountCardAssignController::class, 'store']);
    Route::post('/categories', [DiscountCardAssignController::class, 'cardCategories']);
    Route::post('/update', [DiscountCardAssignController::class, 'update']);
    Route::post('/getCNIC', [DiscountCardAssignController::class, 'getCNIC']);

});
Route::get('web/v1/discount-card/customer/{customer_id}/discount-history', [DiscountCardAssignController::class, 'discountHistory'])
    ->middleware('auth:sanctum');
