<?php

use App\Http\Controllers\Mobile\MobileAppController;
use App\Http\Controllers\Mobile\MobileAuthController;
use App\Http\Controllers\Mobile\MobileBookingController;
use App\Http\Controllers\Mobile\MobileFleetController;
use App\Http\Controllers\Mobile\MobileNotificationController;
use App\Http\Controllers\Mobile\MobileProfileController;
use App\Http\Controllers\Mobile\MobilePaymentController;
use App\Http\Controllers\Mobile\MobileSavedPassengerController;
use App\Http\Controllers\Mobile\MobileTravelController;
use App\Http\Controllers\Mobile\MobileWalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile/v1')->group(function () {
    Route::get('payments/{payment}/checkout', [MobilePaymentController::class, 'checkout'])
        ->middleware('throttle:20,1')->name('mobile.payments.checkout');
    Route::match(['get', 'post'], 'payments/{payment}/return', [MobilePaymentController::class, 'returned'])
        ->middleware('throttle:30,1')->name('mobile.payments.return');
    Route::get('app/config', [MobileAppController::class, 'show']);

    Route::prefix('auth')->group(function () {
        Route::post('register', [MobileAuthController::class, 'register']);
        Route::post('login', [MobileAuthController::class, 'login']);
        Route::post('forgot-password', [MobileAuthController::class, 'forgotPassword']);
        Route::post('verify-reset-otp', [MobileAuthController::class, 'verifyPasswordResetOtp']);
        Route::post('reset-password', [MobileAuthController::class, 'resetPassword']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [MobileAuthController::class, 'logout']);
        Route::post('auth/resend-otp', [MobileAuthController::class, 'resendOtp']);
        Route::post('auth/verify-otp', [MobileAuthController::class, 'verifyOtp']);

        Route::get('profile', [MobileProfileController::class, 'show']);
        Route::put('profile', [MobileProfileController::class, 'update']);
        Route::get('fleet', [MobileFleetController::class, 'index']);
        Route::get('wallet', [MobileWalletController::class, 'show']);

        Route::get('saved-passengers', [MobileSavedPassengerController::class, 'index']);
        Route::post('saved-passengers', [MobileSavedPassengerController::class, 'store']);
        Route::put('saved-passengers/{savedPassenger}', [MobileSavedPassengerController::class, 'update']);
        Route::delete('saved-passengers/{savedPassenger}', [MobileSavedPassengerController::class, 'destroy']);

        Route::get('cities', [MobileTravelController::class, 'cities']);
        Route::get('destinations', [MobileTravelController::class, 'destinations']);
        Route::get('schedules', [MobileTravelController::class, 'schedules']);
        Route::get('schedules/{scheduleDetail}/seats', [MobileTravelController::class, 'seats']);

        Route::post('bookings/quote', [MobileBookingController::class, 'quote']);
        Route::post('bookings', [MobileBookingController::class, 'store']);
        Route::get('bookings', [MobileBookingController::class, 'index']);
        Route::post('bookings/{invoice}/payment/refresh', [MobilePaymentController::class, 'refresh'])->middleware('throttle:10,1');
        Route::get('bookings/{invoice}', [MobileBookingController::class, 'show']);

        Route::get('notifications', [MobileNotificationController::class, 'index']);
    });
});
