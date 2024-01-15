<?php

use App\Http\Controllers\Api\RoleController;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\Discount\DiscountController;
use App\Http\Controllers\FareTableController;
use App\Http\Controllers\Surcharge\SurchargeController;
use App\Http\Controllers\TerminalController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Api\BookingApiController;
use App\Http\Controllers\Api\TicketingApiController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//
//Route::post("/login",[AuthController::class,'login']);
//Route::post("/double-check",[AuthController::class,'doubleCheck']);
//Route::get("/logout",[AuthController::class,'logout'])->middleware([CustomMiddleware::class]);
//

// Route::post('register', [RegisterController::class, 'register']);

