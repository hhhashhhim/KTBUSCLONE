<?php

use App\Http\Controllers\Api\CallCenterController;
use App\Http\Controllers\Api\CallCenterInformationController;
use App\Http\Controllers\Api\CallCenterNotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('web/v1/call-center')->middleware('auth:sanctum')->group(function () {
    Route::get('options', [CallCenterController::class, 'options']);
    Route::get('notifications', [CallCenterNotificationController::class, 'index']);
    Route::post('notifications/{id}/read', [CallCenterNotificationController::class, 'read'])->whereNumber('id');
    Route::get('passengers', [CallCenterController::class, 'passengers'])->middleware('throttle:30,1');
    Route::get('history', [CallCenterController::class, 'history']);
    Route::get('quick-answers/options', [CallCenterInformationController::class, 'options']);
    Route::get('quick-answers', [CallCenterInformationController::class, 'index']);
    Route::get('calls', [CallCenterController::class, 'index']);
    Route::post('calls', [CallCenterController::class, 'store']);
    Route::get('calls/{id}', [CallCenterController::class, 'show'])->whereNumber('id');
    Route::patch('calls/{id}', [CallCenterController::class, 'update'])->whereNumber('id');
    Route::get('calls/{id}/complaint-assignees', [CallCenterController::class, 'complaintAssignees'])->whereNumber('id');
    Route::post('calls/{id}/assign-complaint', [CallCenterController::class, 'assignComplaint'])->whereNumber('id');
    Route::post('calls/{id}/close-complaint', [CallCenterController::class, 'closeComplaint'])->whereNumber('id');
    Route::get('calls/{id}/complaint-updates', [CallCenterController::class, 'complaintUpdates'])->whereNumber('id');
    Route::post('calls/{id}/complaint-updates', [CallCenterController::class, 'addComplaintUpdate'])->whereNumber('id');
    Route::get('summary', [CallCenterController::class, 'summary']);
    Route::get('daily-report', [CallCenterController::class, 'dailyReport']);
    Route::post('shift-review', [CallCenterController::class, 'reviewShift']);
    Route::post('daily-review', [CallCenterController::class, 'reviewDaily']);
});
