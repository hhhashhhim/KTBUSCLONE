<?php

use App\Http\Controllers\Setting\Profile\ProfileController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'web/v1/settings/profile','middleware' => ['auth:sanctum']], function () {
    Route::post('/', [ProfileController::class, 'index']);
    Route::post('/store', [ProfileController::class, 'store']);
    Route::post('/update', [ProfileController::class, 'update']);
    Route::post('/delete', [ProfileController::class, 'delete']);
});
