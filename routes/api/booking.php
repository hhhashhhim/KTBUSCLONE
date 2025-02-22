<?php

use App\Http\Controllers\Booking\BookingController;
use App\Http\Controllers\Booking\CounterExpensesController;
use App\Http\Controllers\Schedule\ScheduleClosingController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'web/v1/counter/expenses','middleware' => ['auth:sanctum']], function () {
    Route::post('/', [CounterExpensesController::class, 'index']);
    Route::post('/store', [CounterExpensesController::class, 'store']);
    Route::post('/update', [CounterExpensesController::class, 'update']);
});
Route::group(['prefix' => 'web/v1/booking','middleware' => ['auth:sanctum']], function () {
    Route::post('/', [BookingController::class, 'index']);
    Route::post('/cities', [BookingController::class, 'cities']);
    Route::post('/store', [BookingController::class, 'store']);
    Route::post('/send-otp', [BookingController::class, 'sendOtp']);
    Route::post('/verify-otp', [BookingController::class, 'verifyOtp']);
    Route::post('/terminals', [BookingController::class, 'getTerminals']);
    // Route::post('/delete', [BookingController::class, 'deleteBooking']);
    Route::post('/getCNIC', [BookingController::class, 'getCnic']);
    Route::post('/getPoints', [BookingController::class, 'getPoints']);
    Route::post('/usagePoints', [BookingController::class, 'usagePoints']);
    // Route::post('/details', [BookingController::class, 'detailTicket']);
    Route::post('/reschedule', [BookingController::class, 'singleReschedule']);
    Route::post('/seat-classes', [BookingController::class, 'seatClasses']);
    Route::post('/fetchSchedule', [BookingController::class, 'fetchSpecificSchedule']);
    Route::post('/getDestination', [BookingController::class, 'fetchSpecificDestination']);
    Route::post('/overIssue', [BookingController::class, 'fetchSpecificOverIssueSeat']);
    Route::post('/overIssueAdd', [BookingController::class, 'overIssueAddNew']);
    Route::post('/advance', [BookingController::class, 'advanceData']);
    Route::post('/canceling', [BookingController::class, 'cancelingBooking']);
    Route::post('/canceling/all', [BookingController::class, 'cancelingAllBooking']);
    Route::post('/elt', [BookingController::class, 'bookingElt']);
    // Route::post('/getPassenger', [BookingController::class, 'getPassengersList']);
    Route::post('/getClosingData', [BookingController::class, 'getClosingData']);
    Route::post('/getBusClasses', [BookingController::class, 'getBusClasses']);
    Route::post('/busclass/update', [BookingController::class, 'updateBusClass']);
    Route::post('/dropSchedule', [BookingController::class, 'dropSchedule']);
    Route::post('/revertDropSchedule', [BookingController::class, 'revertDropSchedule']);
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
    Route::post('/whatsapp/message', [BookingController::class, 'whatsappMessage']);
    Route::post('/whatsapp/cancel/message', [BookingController::class, 'whatsappCancelMessage']);

    // Schedule Closing
    Route::group(['prefix' => '/close/schedule', [CustomMiddleware::class]], function () {
        Route::post('/fetch', [ScheduleClosingController::class, 'fetchSchedule']);

        Route::group(['prefix' => '/unclosing', [CustomMiddleware::class]], function () {
            Route::post('/', [ScheduleClosingController::class, 'unclosing']);
            Route::post('/hide', [ScheduleClosingController::class, 'hideUnclosing']);
            Route::post('/spare', [ScheduleClosingController::class, 'spareUnclosing']);
            Route::post('/revert', [ScheduleClosingController::class, 'revertUnclosing']);
        });


        Route::group(['prefix' => '/closing', [CustomMiddleware::class]], function () {
            Route::post('/', [ScheduleClosingController::class, 'closing']);
            Route::post('/store', [ScheduleClosingController::class, 'store']);
            Route::post('/merge', [ScheduleClosingController::class, 'mergeClosing']);
            Route::post('/release', [ScheduleClosingController::class, 'releaseClosing']);
            Route::post('/update', [ScheduleClosingController::class, 'update']);
            Route::post('/date/update', [ScheduleClosingController::class, 'updateClosingDate']);
            Route::post('/members', [ScheduleClosingController::class, 'getMembers']);
        });

        Route::group(['prefix' => '/merges', [CustomMiddleware::class]], function () {
            Route::post('/', [ScheduleClosingController::class, 'merges']);
        });
    });

});


























