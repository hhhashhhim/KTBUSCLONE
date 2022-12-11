<?php

use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Booking\AllBookingController;
use App\Http\Controllers\Booking\BookingController;
use App\Http\Controllers\Refreshment\HotelController;
use App\Http\Controllers\Refreshment\FoodController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;




Route::group(['prefix' => 'refreshments', 'middleware', [CustomMiddleware::class]], function () {

    Route::group(['prefix' => '/hotels', 'middleware', [CustomMiddleware::class]], function () {
        Route::post('/', [HotelController::class, 'index']);
        Route::post('/store', [HotelController::class, 'store']);
        Route::post('/update', [HotelController::class, 'update']);
        
        Route::group(['prefix' => '/foods', 'middleware', [CustomMiddleware::class]], function () {
            Route::post('/specific', [FoodController::class, 'index']);
        });
    });

});
