<?php

use App\Http\Controllers\Maintenance\FleetMaintenancePartController;
use App\Http\Controllers\Maintenance\FleetMaintenanceController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;




Route::group(['prefix' => 'web/v1/fleet/maintenance/part','middleware' => ['auth:sanctum']], function () {
    Route::post('/', [FleetMaintenancePartController::class, 'index']);
    Route::post('/store', [FleetMaintenancePartController::class, 'store']);
    Route::post('/update', [FleetMaintenancePartController::class, 'update']);
});


Route::group(['prefix' => 'web/v1/fleet','middleware' => ['auth:sanctum']], function () {
    Route::post('/', [FleetMaintenanceController::class, 'index']);
    Route::post('/part/link', [FleetMaintenanceController::class, 'fleetPartLink']);
    Route::post('/part/link/update', [FleetMaintenanceController::class, 'updateFleetPartLink']);
    Route::post('/single/part/link', [FleetMaintenanceController::class, 'fleetSinglePartLink']);
    Route::post('/meter/reading/update', [FleetMaintenanceController::class, 'updateMeterReading']);
});


Route::group(['prefix' => 'web/v1/fleet/maintenance','middleware' => ['auth:sanctum']], function () {
    Route::post('/due', [FleetMaintenanceController::class, 'dueMaintenance']);
    Route::post('/due/add', [FleetMaintenanceController::class, 'dueMaintenanceAdd']);
    Route::post('/record', [FleetMaintenanceController::class, 'maintenanceRecord']);
    Route::post('/due/update', [FleetMaintenanceController::class, 'dueMaintenanceUpdate']);
});
