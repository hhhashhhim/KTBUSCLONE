<?php

use App\Http\Controllers\Booking\BookingController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::get('print/{id}/pdf', [BookingController::class, 'pdf'])->middleware(CustomMiddleware::class);
Route::post('print/pdf/passenger/list', [BookingController::class, 'passengerListPdf'])->middleware(CustomMiddleware::class);
Route::get('print/{id}/pdf/duplicate', [BookingController::class, 'duplicatePdf'])->middleware(CustomMiddleware::class);
