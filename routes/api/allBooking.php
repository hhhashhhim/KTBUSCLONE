<?php

use App\Http\Controllers\Booking\AllBookingController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'web/v1/allBooking', 'middleware' => ['auth:sanctum']], function () {
    Route::post('/routes', [AllBookingController::class, 'routes']);
    Route::post('/terminals', [AllBookingController::class, 'terminals']);
    Route::post('/buses', [AllBookingController::class, 'buses']);
    Route::post('/filter', [AllBookingController::class, 'filter']);
    Route::post('/jazzcashfilter', [AllBookingController::class, 'jazzcashfilter']);
    Route::post('/refund', [AllBookingController::class, 'refund']);
    // routes/api.php
    Route::post('/jazzcash-payment', [AllBookingController::class, 'jazzcashPayment']);
});
