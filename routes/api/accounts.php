<?php

use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Account\AccountHeadController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'web/v1/accounts','middleware' => ['auth:sanctum']], function () {

    Route::get('/{first}/second', [AccountController::class,'secondLevelOfFirst']);

        Route::group(['prefix' => '/groups', [CustomMiddleware::class]], function () {
            Route::post('/', [AccountController::class, 'accountGroups']);
            Route::post('/second', [AccountController::class, 'getThirdLevel']);
            Route::get('/{second}/third', [AccountController::class,'thirdLevelOfSecond']);
            Route::get('/{third}/fourth', [AccountController::class,'fourthLevelOfThird']);
            Route::post('/store', [AccountController::class, 'groupStore']);
            Route::post('/update', [AccountController::class, 'groupUpdate']);
        });

        Route::group(['prefix' => '/heads', [CustomMiddleware::class]], function () {
            Route::post('/add', [AccountHeadController::class,'headStore']);
            Route::get('/', [AccountHeadController::class,'accountHeads']);
            Route::post('/update', [AccountHeadController::class,'headUpdate']);

            Route::group(['prefix' => '/banks', [CustomMiddleware::class]], function () {
                Route::post('/add', [AccountHeadController::class,'headBankStore']);
                Route::get('/', [AccountHeadController::class,'accountHeadBanks']);
                Route::post('/update', [AccountHeadController::class,'headBankUpdate']);
            });
            
            Route::group(['prefix' => '/cash', [CustomMiddleware::class]], function () {
                Route::post('/add', [AccountHeadController::class,'headCashStore']);
                Route::get('/', [AccountHeadController::class,'accountHeadCash']);
                Route::post('/update', [AccountHeadController::class,'headCashUpdate']);
            });
        });
});
