<?php

use App\Http\Controllers\Booking\BookingController;
use App\Http\Controllers\Expense\ExpenseController;
use App\Http\Controllers\Report\ConfirmCancellationReportController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::post('web/v1/print/pdf/terminal/invoice', [BookingController::class, 'terminalInvoice'])->middleware(CustomMiddleware::class);
Route::post('web/v1/print/pdf/bus/invoice', [BookingController::class, 'busInvoice'])->middleware(CustomMiddleware::class);
Route::post('web/v1/print/pdf/passenger/list', [BookingController::class, 'passengerListPdf'])->middleware(CustomMiddleware::class);
Route::post('web/v1/print/ticket/duplicate', [BookingController::class, 'duplicatePdf'])->middleware(CustomMiddleware::class);
Route::post('web/v1/print/pdf/customer/ticket', [BookingController::class, 'ticketPdf'])->middleware(CustomMiddleware::class);
Route::post('web/v1/print/pdf/customer/elt', [BookingController::class, 'eltPdf'])->middleware(CustomMiddleware::class);
Route::post('web/v1/print/pdf/daily/summary/report', [ExpenseController::class, 'dailySummery'])->middleware(CustomMiddleware::class);
Route::post('web/v1/print/pdf/confirm/cancellation/report', [ConfirmCancellationReportController::class, 'getPrintPdf'])->middleware(CustomMiddleware::class);
