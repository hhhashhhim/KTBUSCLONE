<?php

use App\Http\Controllers\Booking\BookingController;
use App\Http\Controllers\Booking\CounterExpensesController;
use App\Http\Controllers\Schedule\ScheduleClosingController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'counter/expenses', [CustomMiddleware::class]], function () {
    Route::post('/', [CounterExpensesController::class, 'index']);
    Route::post('/store', [CounterExpensesController::class, 'store']);
    Route::post('/update', [CounterExpensesController::class, 'update']);
});
Route::group(['prefix' => 'booking', [CustomMiddleware::class]], function () {
    Route::post('/', [BookingController::class, 'index']);
    Route::post('/cities', [BookingController::class, 'cities']);
    Route::post('/store', [BookingController::class, 'store']);
    Route::post('/terminals', [BookingController::class, 'getTerminals']);
    Route::post('/delete', [BookingController::class, 'deleteBooking']);
    Route::post('/getCNIC', [BookingController::class, 'getCnic']);
    Route::post('/getPoints', [BookingController::class, 'getPoints']);
    Route::post('/usagePoints', [BookingController::class, 'usagePoints']);
    Route::post('/details', [BookingController::class, 'detailTicket']);
    Route::post('/reschedule', [BookingController::class, 'singleReschedule']);
    Route::post('/seat-classes', [BookingController::class, 'seatClasses']);
    Route::post('/fetchSchedule', [BookingController::class, 'fetchSpecificSchedule']);
    Route::post('/getDestination', [BookingController::class, 'fetchSpecificDestination']);
    Route::post('/overIssue', [BookingController::class, 'fetchSpecificOverIssueSeat']);
    Route::post('/overIssueAdd', [BookingController::class, 'overIssueAddNew']);
    Route::post('/advance', [BookingController::class, 'advanceData']);
    Route::post('/canceling', [BookingController::class, 'cancelingBooking']);
    Route::post('/elt', [BookingController::class, 'bookingElt']);
    Route::post('/getPassenger', [BookingController::class, 'getPassengersList']);
    Route::post('/getClosingData', [BookingController::class, 'getClosingData']);
    Route::post('/dropSchedule', [BookingController::class, 'dropSchedule']);
    Route::post('/fare_class', [BookingController::class, 'getFareClass']);
    Route::post('/schedule/selected', [BookingController::class, 'selected']);
    Route::post('/schedule/dropCheck', [BookingController::class, 'dropCheck']);
    Route::post('/terminal/seats', [BookingController::class, 'terminalSeats']);
    Route::post('/check/bus/assigned', [BookingController::class, 'checkAssignedBus']);
    Route::post('/booked/seats/elt/detail', [BookingController::class, 'fetchELTDetails']);
    Route::post('/discount/surcharge/fetch', [BookingController::class, 'fetchScheduleSurchargeDiscount']);
    Route::post('/schedule/terminal/discount/fetch', [BookingController::class, 'fetchTerminalDiscount']);
    Route::post('/elt/fetch/old', [BookingController::class, 'getFetchOldELT']);
    Route::post('/fetch/over/issue/seat', [BookingController::class, 'fetchOverIssueSeat']);
    Route::post('/revert/over/issue/seat', [BookingController::class, 'revertOverIssueSeat']);

    // Schedule Closing
    Route::group(['prefix' => '/close/schedule', [CustomMiddleware::class]], function () {
        Route::post('/fetch', [ScheduleClosingController::class, 'fetchSchedule']);

        Route::group(['prefix' => '/closing', [CustomMiddleware::class]], function () {
            Route::post('/', [ScheduleClosingController::class, 'index']);
            Route::post('/store', [ScheduleClosingController::class, 'store']);
            Route::post('/update', [ScheduleClosingController::class, 'update']);
        });

        Route::group(['prefix' => '/merges', [CustomMiddleware::class]], function () {
            Route::post('/', [ScheduleClosingController::class, 'merges']);
        });
    });

});


























