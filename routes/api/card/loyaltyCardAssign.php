<?php

use App\Http\Controllers\Card\CardAssignController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'web/v1/loyaltyCardAssign','middleware' => ['auth:sanctum']], function () {
    Route::post('/', [CardAssignController::class, 'index']);
    Route::post('/store', [CardAssignController::class, 'store']);
    Route::post('/categories', [CardAssignController::class, 'cardCategories']);
    Route::post('/update', [CardAssignController::class, 'update']);
    Route::post('/getCNIC', [CardAssignController::class, 'getCNIC']);

});
