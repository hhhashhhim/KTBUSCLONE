<?php

use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Booking\AllBookingController;
use App\Http\Controllers\Booking\BookingController;
use App\Http\Controllers\Maintenance\FleetMaintenancePartController;
use App\Http\Controllers\Maintenance\FleetMaintenanceController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;




Route::group(['prefix' => 'fleet/maintenance/part', 'middleware', [CustomMiddleware::class]], function () {
    Route::post('/', [FleetMaintenancePartController::class, 'index']);
    Route::post('/store', [FleetMaintenancePartController::class, 'store']);
    Route::post('/update', [FleetMaintenancePartController::class, 'update']);
});


Route::group(['prefix' => 'fleet', 'middleware', [CustomMiddleware::class]], function () {
    Route::post('/all', [FleetMaintenanceController::class, 'allFleets']);
    Route::post('/part/all', [FleetMaintenanceController::class, 'allParts']);
    Route::post('/part/link', [FleetMaintenanceController::class, 'fleetPartLink']);
});

