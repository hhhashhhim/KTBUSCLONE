<?php

use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Booking\BookingController;
use App\Http\Controllers\Bus\BusClassController;
use App\Http\Controllers\Bus\BusController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\Discount\DiscountController;
use App\Http\Controllers\FareClass\FareClassController;
use App\Http\Controllers\FareTableController;
use App\Http\Controllers\Schedule\ScheduleController;
use App\Http\Controllers\Surcharge\SurchargeController;
use App\Http\Controllers\TerminalController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::post("/login", [AuthController::class, 'login']);
Route::post("/double-check", [AuthController::class, 'doubleCheck']);
Route::get("/logout", [AuthController::class, 'logout'])->middleware([CustomMiddleware::class]);


// Route::group(['prefix'=>'admin'],function(){
//     Route::get('dashboard',[TestController::class,'index'])->name('dashboard');
//     Route::get('company/add',[TestController::class,'add_company'])->name('company.add');
//     Route::get('company/all',[TestController::class,'all_companies'])->name('company.all');
// });


Route::group(['prefix' => 'role', 'middleware', [CustomMiddleware::class]], function () {
    Route::post('/', [RoleController::class, 'index']);
    Route::post('store', [RoleController::class, 'store']);
    Route::post('update', [RoleController::class, 'update']);
    Route::post('delete', [RoleController::class, 'delete']);
    Route::post('/get', [RoleController::class, 'role']);
});
Route::group(['prefix' => 'company', 'middleware', [CustomMiddleware::class]], function () {
    Route::post('/', [CompanyController::class, 'index']);
    Route::post('store', [CompanyController::class, 'store']);
    Route::post('logo-upload', [CompanyController::class, 'logoUpload']);
    Route::post('update', [CompanyController::class, 'update']);
    Route::post('delete', [CompanyController::class, 'delete']);
    Route::post('/get', [CompanyController::class, 'role']);
    Route::post('/roles', [CompanyController::class, 'company_roles']);
    Route::post('/get', [CompanyController::class, 'company']);
});
Route::group(['prefix' => 'user', [CustomMiddleware::class]], function () {
    Route::post('/', [UserController::class, 'index']);
    Route::post('store', [UserController::class, 'store']);
    Route::post('update', [UserController::class, 'update']);
    Route::post('delete', [UserController::class, 'delete']);
    Route::post('permissions', [UserController::class, 'permissions']);
});
Route::group(['prefix' => 'terminals', [CustomMiddleware::class]], function () {
    Route::post('/', [TerminalController::class, 'index']);
    Route::post('/getTerminal', [TerminalController::class, 'getTerminal']);
    Route::post('store', [TerminalController::class, 'store']);
    Route::post('update', [TerminalController::class, 'update']);
    Route::post('delete', [TerminalController::class, 'delete']);
    Route::post('permissions', [TerminalController::class, 'permissions']);
});

Route::group(['prefix' => 'cities', [CustomMiddleware::class]], function () {
    Route::post('/', [CityController::class, 'index']);
    Route::post('store', [CityController::class, 'store']);
    Route::post('update', [CityController::class, 'update']);
    Route::post('delete', [CityController::class, 'delete']);
    Route::post('/terminals', [CityController::class, 'cityTerminals']);
    Route::post('/routes', [CityController::class, 'cityRoutes']);
    Route::post('/routes/list', [CityController::class, 'city_routes_list']);
    Route::post('/routes/details', [CityController::class, 'city_routes_details']);
});

Route::group(['prefix' => 'fare-table', [CustomMiddleware::class]], function () {

    Route::post('/', [FareTableController::class, 'record']);
    Route::post('/store', [FareTableController::class, 'store']);
    Route::post('/fare_class/get', [FareTableController::class, 'getFareClass']);
    Route::post('/check', [FareTableController::class, 'check']);
});

Route::group(['prefix' => 'fare-class', [CustomMiddleware::class]], function () {

    Route::post('/', [FareClassController::class, 'index']);
    Route::post('/store', [FareClassController::class, 'storeFareClass']);
    Route::post('/update', [FareClassController::class, 'updateFareClass']);
    Route::post('/delete', [FareClassController::class, 'deleteFareClass']);
});

Route::group(['prefix' => 'discount', [CustomMiddleware::class]], function () {
    Route::post('/', [DiscountController::class, 'index']);
    Route::post('/store', [DiscountController::class, 'storeDiscount']);
    Route::post('/update', [DiscountController::class, 'updateDiscount']);
    Route::post('/delete', [DiscountController::class, 'deleteDiscount']);
    Route::post('/getSelective', [DiscountController::class, 'selectiveDiscount']);
});

Route::group(['prefix' => 'surcharge', [CustomMiddleware::class]], function () {
    Route::post('/', [SurchargeController::class, 'index']);
    Route::post('/store', [SurchargeController::class, 'storeSurcharge']);
    Route::post('/update', [SurchargeController::class, 'updateSurcharge']);
    Route::post('/delete', [SurchargeController::class, 'deleteSurcharge']);
    Route::post('/getSelective', [SurchargeController::class, 'selectiveSurcharge']);
});
Route::group(['prefix' => 'schedule', [CustomMiddleware::class]], function () {
    Route::post('/', [ScheduleController::class, 'index']);
    Route::post('/store', [ScheduleController::class, 'storeSchedule']);
    Route::post('/edit', [ScheduleController::class, 'editSchedule']);
    Route::post('/update', [ScheduleController::class, 'updateSchedule']);
    Route::post('/delete', [ScheduleController::class, 'deleteSchedule']);
    Route::post('/getRoute', [ScheduleController::class, 'getRoutes']);
    Route::post('/getCity', [ScheduleController::class, 'getCity']);
    Route::post('/getEntire', [ScheduleController::class, 'getEntire']);
    Route::post('/getRouteFare', [ScheduleController::class, 'getRouteFareClass']);
    Route::post('/genericCommon', [ScheduleController::class, 'genericCommon']);
    Route::post('/selected', [ScheduleController::class, 'selected']);

});
Route::group(['prefix' => 'buses', [CustomMiddleware::class]], function () {
    Route::post('/', [BusController::class, 'index']);
    Route::post('/store', [BusController::class, 'storeBus']);
    Route::post('/update', [BusController::class, 'updateBus']);
    Route::post('/delete', [BusController::class, 'deleteBus']);
    Route::post('/getBusData', [BusController::class, 'getBusData']);
});

Route::group(['prefix' => 'bus_classes', [CustomMiddleware::class]], function () {
    Route::post('/', [BusClassController::class, 'index']);
    Route::post('/store', [BusClassController::class, 'storeBusClass']);
    Route::post('/update', [BusClassController::class, 'updateBusClass']);
    Route::post('/delete', [BusClassController::class, 'deleteBusClass']);
});

Route::group(['prefix' => 'booking', [CustomMiddleware::class]], function () {
    Route::post('/', [BookingController::class, 'index']);
    Route::post('/store', [BookingController::class, 'store']);
    Route::post('/delete', [BookingController::class, 'deleteBooking']);
    Route::post('/getCNIC', [BookingController::class, 'getCnic']);
    Route::post('/detail', [BookingController::class, 'detailTicket']);
});
Route::get('/{any}', [AuthController::class, 'index'])->where('any', '.*');
