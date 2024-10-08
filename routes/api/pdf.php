<?php

use App\Http\Controllers\Booking\BookingController;
use App\Http\Controllers\Expense\ExpenseController;
use App\Http\Controllers\Account\Pdf\TransactionPdfController;
use App\Http\Controllers\Report\ConfirmCancellationReportController;
use App\Http\Controllers\Report\OverissueReportController;
use App\Http\Controllers\Report\RescheduleReportController;
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
    Route::post('web/v1/print/pdf/over-issue/report', [OverissueReportController::class, 'getPrintPdf']);
    Route::post('web/v1/print/pdf/reschedule/report', [RescheduleReportController::class, 'getPrintPdf']);
    Route::post('web/v1/booking/close/schedule/merges/pdf', [ScheduleClosingController::class, 'mergesPdf']);



    Route::group(['prefix' => 'web/v1/accounts','middleware' => ['custom.sanctum.token.verify']], function () {
        Route::prefix('transactions')->group(function () {
            Route::prefix('data')->group(function () {
                Route::post('/pdf', [TransactionPdfController::class,'transactionPdf']);
                Route::post('receipts/pdf', [TransactionPdfController::class,'receiptPdf']);
                Route::post('general/ledger/pdf', [TransactionPdfController::class,'generalLedgerPdf']);
                Route::post('ledger/pdf', [TransactionPdfController::class,'ledgerPdf']);
                Route::post('general/journal/pdf', [TransactionPdfController::class,'journalPdf']);
                Route::post('general/trial/pdf', [TransactionPdfController::class,'generalTrialPdf']);
                Route::post('daily/report/pdf', [TransactionPdfController::class,'dailyReportPdf']);
            });
        });
    });
});