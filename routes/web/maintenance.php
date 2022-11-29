<?php

use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Booking\AllBookingController;
use App\Http\Controllers\Booking\BookingController;
use App\Http\Controllers\Maintenance\Part\FleetMaintenancePartController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;




Route::group(['prefix' => 'fleet/maintenance/part', 'middleware', [CustomMiddleware::class]], function () {
    Route::post('/', [FleetMaintenancePartController::class, 'index']);
    Route::post('/store', [FleetMaintenancePartController::class, 'store']);
    Route::post('/update', [FleetMaintenancePartController::class, 'update']);
    Route::post('/delete', [FleetMaintenancePartController::class, 'delete']);
    Route::post('/get', [FleetMaintenancePartController::class, 'role']);
});

