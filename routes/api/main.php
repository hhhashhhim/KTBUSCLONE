<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\BookingApiController;
use App\Http\Controllers\Api\TicketingApiController;
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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });


// Route::post('register', [RegisterController::class, 'register']);
Route::post('v1/login', [AuthApiController::class, 'login']);

Route::group(['middleware' => 'auth:sanctum'], function(){
   //All secure URL's
   Route::group(['prefix'=>'v1/booking'],function(){
      Route::post('/cities/departures',[BookingApiController::class,'departureCities']);
      Route::post('/cities/destinations',[BookingApiController::class,'destinationCities']);
      Route::post('/schedules/available',[BookingApiController::class,'availableSchedules']);
      Route::post('/schedule/preview',[BookingApiController::class,'previewSchedule']);
      Route::post('/new',[BookingApiController::class,'bookSeat']);
      Route::post('/update/terminal/data',[BookingApiController::class,'updateSeatTerminalData']);
   });
   
   Route::group(['prefix'=>'v1/tickets'],function(){
      Route::post('/status/check',[BookingApiController::class,'checkTicketsStatus']);
   });
   
   Route::group(['prefix'=>'v1/checkk'],function(){
      Route::post('/cities/departures',[TicketingApiController::class,'departureCities']);
      Route::post('/cities/destinations',[TicketingApiController::class,'destinationCities']);
      Route::post('/schedules/available',[TicketingApiController::class,'availableSchedules']);
      Route::post('/schedule/preview',[TicketingApiController::class,'previewSchedule']);
      Route::post('/new',[TicketingApiController::class,'bookSeat']);
   });
  
});