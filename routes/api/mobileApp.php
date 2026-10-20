<?php

use App\Http\Controllers\Api\MobileAppAdminController;
use App\Http\Controllers\Api\MobileAppSettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('web/v1/mobile-app')->middleware('auth:sanctum')->group(function () {
    Route::get('notifications', [\App\Http\Controllers\Api\MobileAppNotificationController::class, 'index']);
    Route::post('notifications/preview', [\App\Http\Controllers\Api\MobileAppNotificationController::class, 'preview'])->middleware('throttle:20,1');
    Route::post('notifications/send', [\App\Http\Controllers\Api\MobileAppNotificationController::class, 'send'])->middleware('throttle:5,1');
    Route::get('options', [MobileAppAdminController::class, 'options']);
    Route::get('overview', [MobileAppAdminController::class, 'overview']);
    Route::get('engagement', [MobileAppAdminController::class, 'engagement']);
    Route::get('availability/routes', [MobileAppAdminController::class, 'availabilityRoutes']);
    Route::get('availability/schedules', [MobileAppAdminController::class, 'availabilitySchedules']);
    Route::get('users', [MobileAppAdminController::class, 'users']);
    Route::get('users/{id}', [MobileAppAdminController::class, 'user'])->whereNumber('id');
    Route::get('users/{id}/wallet', [\App\Http\Controllers\Api\MobileAppWalletController::class, 'show'])->whereNumber('id');
    Route::put('users/{id}/wallet', [\App\Http\Controllers\Api\MobileAppWalletController::class, 'update'])->whereNumber('id');
    Route::put('users/{id}/limits', [MobileAppAdminController::class, 'updateUserLimits'])->whereNumber('id');
    Route::post('users/{id}/require-password-reset', [MobileAppAdminController::class, 'requirePasswordReset'])->whereNumber('id');
    Route::get('policies', [MobileAppSettingsController::class, 'policies']);
    Route::put('policies', [MobileAppSettingsController::class, 'updatePolicies']);
    Route::put('users/{id}/status', [MobileAppAdminController::class, 'updateUserStatus'])->whereNumber('id');
    Route::post('users/{id}/revoke-sessions', [MobileAppAdminController::class, 'revokeSessions'])->whereNumber('id');
    Route::get('bookings', [MobileAppAdminController::class, 'bookings']);
    Route::get('bookings/{id}', [MobileAppAdminController::class, 'booking'])->whereNumber('id');
    Route::post('bookings/{id}/remove-seats', [MobileAppAdminController::class, 'removeSeats'])->whereNumber('id');
    Route::get('payments', [MobileAppAdminController::class, 'payments']);
    Route::get('reports/export', [MobileAppAdminController::class, 'exportReports']);
    Route::get('reports', [MobileAppAdminController::class, 'reports']);
    Route::get('activity', [MobileAppAdminController::class, 'activity']);
    Route::get('settings', [MobileAppSettingsController::class, 'settings']);
    Route::put('settings', [MobileAppSettingsController::class, 'updateSettings']);
    Route::get('content', [MobileAppSettingsController::class, 'content']);
    Route::post('content', [MobileAppSettingsController::class, 'storeContent']);
    Route::put('content/{id}', [MobileAppSettingsController::class, 'updateContent'])->whereNumber('id');
    Route::delete('content/{id}', [MobileAppSettingsController::class, 'destroyContent'])->whereNumber('id');
});
