<?php

use App\Http\Controllers\Booking\BookingController;
use App\Http\Controllers\Expense\ExpenseController;
use App\Http\Controllers\Report\ConfirmCancellationReportController;
use App\Http\Controllers\Schedule\ScheduleClosingController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;


Route::group(['middleware' => ['custom.sanctum.token.verify']], function () {
    Route::post('web/v1/print/pdf/terminal/invoice', [BookingController::class, 'terminalInvoice']);
    Route::post('web/v1/print/pdf/bus/invoice', [BookingController::class, 'busInvoice']);
    Route::post('web/v1/print/pdf/passenger/list', [BookingController::class, 'passengerListPdf']);
    Route::post('web/v1/print/ticket/duplicate', [BookingController::class, 'duplicatePdf']);
    Route::post('web/v1/print/pdf/customer/ticket', [BookingController::class, 'ticketPdf']);
    Route::post('web/v1/print/pdf/customer/elt', [BookingController::class, 'eltPdf']);
    Route::post('web/v1/print/pdf/daily/summary/report', [ExpenseController::class, 'dailySummery']);
    Route::post('web/v1/print/pdf/confirm/cancellation/report', [ConfirmCancellationReportController::class, 'getPrintPdf']);
    Route::post('web/v1/booking/close/schedule/merges/pdf', [ScheduleClosingController::class, 'mergesPdf']);
});