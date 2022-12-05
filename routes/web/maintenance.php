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
    Route::post('/', [FleetMaintenanceController::class, 'index']);
    Route::post('/part/link', [FleetMaintenanceController::class, 'fleetPartLink']);
    Route::post('/part/link/update', [FleetMaintenanceController::class, 'updateFleetPartLink']);
    Route::post('/single/part/link', [FleetMaintenanceController::class, 'fleetSinglePartLink']);
    Route::post('/meter/reading/update', [FleetMaintenanceController::class, 'updateMeterReading']);
});


Route::group(['prefix' => 'fleet/maintenance', 'middleware', [CustomMiddleware::class]], function () {
    Route::post('/due', [FleetMaintenanceController::class, 'dueMaintenance']);
    Route::post('/due/add', [FleetMaintenanceController::class, 'dueMaintenanceAdd']);
    Route::post('/record', [FleetMaintenanceController::class, 'maintenanceRecord']);
    Route::post('/due/update', [FleetMaintenanceController::class, 'dueMaintenanceUpdate']);
});
