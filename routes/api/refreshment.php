<?php

use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Booking\AllBookingController;
use App\Http\Controllers\Booking\BookingController;
use App\Http\Controllers\Refreshment\HotelController;
use App\Http\Controllers\Refreshment\FoodController;
use App\Http\Controllers\Refreshment\FoodDealController;
use App\Http\Controllers\Refreshment\FoodOrderController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;




Route::group(['prefix' => 'web/v1/refreshments','middleware' => ['auth:sanctum']], function () {

    Route::group(['prefix' => '/hotels'], function () {
        Route::post('/', [HotelController::class, 'index']);
        Route::post('/store', [HotelController::class, 'store']);
        Route::post('/update', [HotelController::class, 'update']);
        
        Route::post('/items', [FoodOrderController::class, 'hotelAllItems']);

        Route::group(['prefix' => '/specific/foods'], function () {
            Route::post('/', [FoodController::class, 'index']);
            Route::post('/store', [FoodController::class, 'store']);
            Route::post('/update', [FoodController::class, 'update']);
            Route::post('/items', [FoodOrderController::class, 'hotelItems']);
            
            Route::group(['prefix' => '/deals'], function () {
                Route::post('/', [FoodDealController::class, 'index']);
                Route::post('/store', [FoodDealController::class, 'store']);
                Route::post('/update', [FoodDealController::class, 'update']);
            });

        });

        Route::group(['prefix' => '/orders'], function () {
            // Route::post('/', [FoodDealController::class, 'index']);
            Route::post('/food', [FoodOrderController::class, 'orderFoodIndex']);
            Route::post('/book', [FoodOrderController::class, 'orderBook']);
            Route::post('/received', [FoodOrderController::class, 'orderReceive']);
            Route::post('/ready', [FoodOrderController::class, 'orderReady']);
            Route::post('/delivered', [FoodOrderController::class, 'orderDelivered']);
        });

    });

});
