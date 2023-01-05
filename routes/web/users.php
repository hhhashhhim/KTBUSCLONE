<?php

use App\Http\Controllers\UserController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'user', [CustomMiddleware::class]], function () {
    Route::post('/', [UserController::class, 'index']);
    Route::post('store', [UserController::class, 'store']);
    Route::post('update', [UserController::class, 'update']);
    Route::post('delete', [UserController::class, 'delete']);
    Route::post('permissions', [UserController::class, 'permissions']);
    Route::post('/update/terminal', [UserController::class, 'updateTerminal']);
});
