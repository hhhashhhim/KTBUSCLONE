<?php

use App\Http\Controllers\UserController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'web/v1/user','middleware' => ['auth:sanctum']], function () {
    Route::post('/', [UserController::class, 'index']);
    Route::post('/cities', [UserController::class, 'getCities']);
    Route::post('/store', [UserController::class, 'store']);
    Route::post('/edit', [UserController::class, 'edit']);
    Route::post('/update', [UserController::class, 'update']);
    Route::post('/hide', [UserController::class, 'hideUser']);
    Route::post('/permissions', [UserController::class, 'permissions']);
    Route::post('/update/terminal', [UserController::class, 'updateTerminal']);
});
