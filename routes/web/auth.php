<?php

use App\Http\Controllers\AuthController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::post("/login", [AuthController::class, 'login']);
Route::post("/double-check", [AuthController::class, 'doubleCheck']);
Route::post("/password/update", [AuthController::class, 'updatePassword']);
Route::get("/logout", [AuthController::class, 'logout'])->middleware([CustomMiddleware::class]);
