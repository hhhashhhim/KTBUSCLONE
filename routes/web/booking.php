<?php

use App\Http\Controllers\Booking\BookingController;
use App\Http\Controllers\Schedule\ScheduleClosingController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'booking', [CustomMiddleware::class]], function () {
    Route::post('/', [BookingController::class, 'index']);
    Route::post('/store', [BookingController::class, 'store']);
    Route::post('/delete', [BookingController::class, 'deleteBooking']);
    Route::post('/getCNIC', [BookingController::class, 'getCnic']);
    Route::post('/details', [BookingController::class, 'detailTicket']);
    Route::post('/reschedule', [BookingController::class, 'reschedule']);
    Route::post('/seat-classes', [BookingController::class, 'seatClasses']);
    Route::post('/fetchSchedule', [BookingController::class, 'fetchSpecificSchedule']);
    Route::post('/getDestination', [BookingController::class, 'fetchSpecificDestination']);
    Route::post('/overIssue', [BookingController::class, 'fetchSpecificOverIssueSeat']);
    Route::post('/overIssueAdd', [BookingController::class, 'overIssueAddNew']);
    Route::post('/advance', [BookingController::class, 'advanceData']);
    Route::post('/canceling', [BookingController::class, 'cancelingBooking']);
    Route::post('/elt', [BookingController::class, 'bookingElt']);
    Route::post('/getPassenger', [BookingController::class, 'getPassengersList']);

    // Schedule Closing
    Route::group(['prefix' => '/schedule', [CustomMiddleware::class]], function () {
        Route::post('/fetch', [ScheduleClosingController::class, 'fetchSchedule']);
        Route::group(['prefix' => '/closing', [CustomMiddleware::class]], function () {
            Route::post('/', [ScheduleClosingController::class, 'index']);
            Route::post('/store', [ScheduleClosingController::class, 'store']);
        });
    });

});


























