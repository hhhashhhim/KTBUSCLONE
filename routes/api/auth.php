<?php

use App\Http\Controllers\AuthController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::post("/v1/login", [AuthController::class, 'login']);
Route::post("/v1/double-check", [AuthController::class, 'doubleCheck']);
Route::post("/v1/password/update", [AuthController::class, 'updatePassword']);
Route::get("/v1/logout", [AuthController::class, 'logout'])->middleware([CustomMiddleware::class]);
