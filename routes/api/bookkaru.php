<?php

use App\Http\Controllers\Api\BookkaruCancellationController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'web/v1/bookkaru/cancellations', 'middleware' => ['auth:sanctum']], function () {
    Route::post('/requests', [BookkaruCancellationController::class, 'requests']);
    Route::post('/approve', [BookkaruCancellationController::class, 'approveRequest']);
    Route::post('/reject', [BookkaruCancellationController::class, 'rejectRequest']);
});
