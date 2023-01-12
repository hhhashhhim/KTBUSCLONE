<?php

use App\Http\Controllers\Booking\BookingController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::post('print/pdf/terminal/invoice', [BookingController::class, 'terminalInvoice'])->middleware(CustomMiddleware::class);
Route::post('print/pdf/bus/invoice', [BookingController::class, 'busInvoice'])->middleware(CustomMiddleware::class);
Route::post('print/pdf/passenger/list', [BookingController::class, 'passengerListPdf'])->middleware(CustomMiddleware::class);
Route::post('print/ticket/duplicate', [BookingController::class, 'duplicatePdf'])->middleware(CustomMiddleware::class);
