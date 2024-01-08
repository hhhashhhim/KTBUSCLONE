<?php


use App\Http\Controllers\RoleController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1/role', 'middleware', [CustomMiddleware::class]], function () {
    Route::post('/', [RoleController::class, 'index']);
    Route::post('store', [RoleController::class, 'store']);
    Route::post('update', [RoleController::class, 'update']);
    Route::post('delete', [RoleController::class, 'delete']);
    Route::post('/get', [RoleController::class, 'role']);
});
