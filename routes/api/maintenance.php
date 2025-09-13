<?php

use App\Http\Controllers\CityController;
use App\Http\Controllers\Maintenance\FaultClaimController;
use App\Http\Controllers\Maintenance\FleetMaintenancePartController;
use App\Http\Controllers\Maintenance\FleetMaintenanceController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;




Route::group(['prefix' => 'web/v1/fleet/maintenance/part', 'middleware' => ['auth:sanctum']], function () {
    Route::post('/', [FleetMaintenancePartController::class, 'index']);
    Route::post('/store', [FleetMaintenancePartController::class, 'store']);
    Route::post('/update', [FleetMaintenancePartController::class, 'update']);
});


Route::group(['prefix' => 'web/v1/fleet', 'middleware' => ['auth:sanctum']], function () {
    Route::post('/', [FleetMaintenanceController::class, 'index']);
    Route::post('/part/link', [FleetMaintenanceController::class, 'fleetPartLink']);
    Route::post('/part/link/update', [FleetMaintenanceController::class, 'updateFleetPartLink']);
    Route::post('/single/part/link', [FleetMaintenanceController::class, 'fleetSinglePartLink']);
    Route::post('/meter/reading/update', [FleetMaintenanceController::class, 'updateMeterReading']);
    Route::post('/single/due/detail', [FleetMaintenanceController::class, 'fleetDueDetail']);
    Route::post('/inspection/results', [FleetMaintenanceController::class, 'fleetInspectionResults']);
});


Route::group(['prefix' => 'web/v1/fleet/maintenance', 'middleware' => ['auth:sanctum']], function () {
    Route::post('/due', [FleetMaintenanceController::class, 'dueMaintenance']);
    Route::get('/due/details', [FleetMaintenanceController::class, 'dueMaintenanceDetails']);
    Route::post('/due/add', [FleetMaintenanceController::class, 'dueMaintenanceAdd']);
    Route::post('/record', [FleetMaintenanceController::class, 'maintenanceRecord']);
    Route::post('/due/update', [FleetMaintenanceController::class, 'dueMaintenanceUpdate']);
});

Route::middleware('auth:sanctum')->prefix('web/v1/fleet/fault-claims')->group(function () {
    Route::post('/', [FaultClaimController::class, 'index']);
    Route::post('/store', [FaultClaimController::class, 'store']);
    Route::post('/show', [FaultClaimController::class, 'show']);
});

Route::middleware('auth:sanctum')->prefix('web/v1/fleet/dock-requests')->group(function () {
    Route::post('/', [FaultClaimController::class, 'requests']);
    Route::post('/show', [FaultClaimController::class, 'showRequest']);
    Route::post('/approve', [FaultClaimController::class, 'approveDockRequest']);
    Route::get('/pending-count', [FaultClaimController::class, 'pendingDockCount']);

});

Route::middleware('auth:sanctum')->prefix('web/v1/fleet/inspection-result')->group(function () {
    Route::post('/data', [FaultClaimController::class, 'helperData']);
    Route::post('/submit', [FaultClaimController::class, 'submitResult']);
});
