<?php

use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Booking\AllBookingController;
use App\Http\Controllers\Booking\BookingController;
use App\Http\Controllers\Bus\BusClassController;
use App\Http\Controllers\Bus\BusController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\Discount\DiscountController;
use App\Http\Controllers\FareClass\FareClassController;
use App\Http\Controllers\FareTableController;
use App\Http\Controllers\Hrm\Department\DepartmentController;
use App\Http\Controllers\Hrm\Designation\DesignationController;
use App\Http\Controllers\Hrm\Employee\EmployeeController;
use App\Http\Controllers\Hrm\Leave\LeaveController;
use App\Http\Controllers\Schedule\ScheduleController;
use App\Http\Controllers\Schedule\ScheduleClosingController;
use App\Http\Controllers\Setting\Tickets\TicketsTemplateController;
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

//Route::get('send', function () {
//
//    $details = [
//        'title' => 'Mail from ItSolutionStuff.com',
//        'body' => 'This is for testing email using smtp'
//    ];
//
//    Mail::to('faizanmanni@gmail.com')->send(new \App\Mail\MyTestMail($details));
//    dd("Email is Sent.");
//});

Route::post("/login", [AuthController::class, 'login']);
Route::post("/double-check", [AuthController::class, 'doubleCheck']);
Route::get("/logout", [AuthController::class, 'logout'])->middleware([CustomMiddleware::class]);


// Route::group(['prefix'=>'admin'],function(){
//     Route::get('dashboard',[TestController::class,'index'])->name('dashboard');
//     Route::get('company/add',[TestController::class,'add_company'])->name('company.add');
//     Route::get('company/all',[TestController::class,'all_companies'])->name('company.all');
// });


require_once('web/maintenance.php');
require_once('web/refreshment.php');
//role Routes
Route::group(['prefix' => 'role', 'middleware', [CustomMiddleware::class]], function () {
    Route::post('/', [RoleController::class, 'index']);
    Route::post('store', [RoleController::class, 'store']);
    Route::post('update', [RoleController::class, 'update']);
    Route::post('delete', [RoleController::class, 'delete']);
    Route::post('/get', [RoleController::class, 'role']);
});
//company Routes
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
//users Routes
Route::group(['prefix' => 'user', [CustomMiddleware::class]], function () {
    Route::post('/', [UserController::class, 'index']);
    Route::post('store', [UserController::class, 'store']);
    Route::post('update', [UserController::class, 'update']);
    Route::post('delete', [UserController::class, 'delete']);
    Route::post('permissions', [UserController::class, 'permissions']);
});
//Terminals Route
Route::group(['prefix' => 'terminals', [CustomMiddleware::class]], function () {
    Route::post('/', [TerminalController::class, 'index']);
    Route::post('/getTerminal', [TerminalController::class, 'getTerminal']);
    Route::post('store', [TerminalController::class, 'store']);
    Route::post('update', [TerminalController::class, 'update']);
    Route::post('delete', [TerminalController::class, 'delete']);
    Route::post('permissions', [TerminalController::class, 'permissions']);
});
//cities Route
Route::group(['prefix' => 'cities', [CustomMiddleware::class]], function () {
    Route::post('/', [CityController::class, 'index']);
    Route::post('store', [CityController::class, 'store']);
    Route::post('update', [CityController::class, 'update']);
    Route::post('delete', [CityController::class, 'delete']);
    Route::post('/terminals', [CityController::class, 'cityTerminals']);
    Route::post('/routes', [CityController::class, 'cityRoutes']);
    Route::post('/routes/update', [CityController::class, 'cityRoutesUpdate']);
    Route::post('/routes/list', [CityController::class, 'city_routes_list']);
    Route::post('/routes/details', [CityController::class, 'city_routes_details']);
});
// FareTable Route
Route::group(['prefix' => 'fare-table', [CustomMiddleware::class]], function () {
    Route::post('/', [FareTableController::class, 'record']);
    Route::post('/store', [FareTableController::class, 'store']);
    Route::post('/fare_class/get', [FareTableController::class, 'getFareClass']);
    Route::post('/check', [FareTableController::class, 'check']);
});

//fare Class  Route
Route::group(['prefix' => 'fare-class', [CustomMiddleware::class]], function () {
    Route::post('/', [FareClassController::class, 'index']);
    Route::post('/store', [FareClassController::class, 'storeFareClass']);
    Route::post('/update', [FareClassController::class, 'updateFareClass']);
    Route::post('/delete', [FareClassController::class, 'deleteFareClass']);
});

// Discount Route
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
    Route::post('/extend', [ScheduleController::class, 'extend']);
    Route::post('/extendedGet', [ScheduleController::class, 'extendedGet']);

});
Route::group(['prefix' => 'buses', [CustomMiddleware::class]], function () {
    Route::post('/', [BusController::class, 'index']);
    Route::post('/store', [BusController::class, 'storeBus']);
    Route::post('/update', [BusController::class, 'updateBus']);
    Route::post('/delete', [BusController::class, 'deleteBus']);
    Route::post('/getBusData', [BusController::class, 'getBusData']);
    Route::post('/storeFareClass', [BusController::class, 'saveFareClass']);

});

Route::group(['prefix' => 'bus_classes', [CustomMiddleware::class]], function () {
    Route::post('/', [BusClassController::class, 'index']);
    Route::post('/store', [BusClassController::class, 'storeBusClass']);
    Route::post('/update', [BusClassController::class, 'updateBusClass']);
    Route::post('/delete', [BusClassController::class, 'deleteBusClass']);
    Route::post('/duplicate', [BusClassController::class, 'duplicateBusClass']);
});

Route::group(['prefix' => 'booking', [CustomMiddleware::class]], function () {
    Route::post('/', [BookingController::class, 'index']);
    Route::post('/store', [BookingController::class, 'store']);
    Route::post('/delete', [BookingController::class, 'deleteBooking']);
    Route::post('/getCNIC', [BookingController::class, 'getCnic']);
    Route::post('/details', [BookingController::class, 'detailTicket']);
    Route::post('/reschedule', [BookingController::class, 'reschedule']);
    Route::post('/seat-classes', [BookingController::class, 'seatClasses']);
    Route::post('/fetchSchedule', [BookingController::class, 'fetchSpecificSchedule']);
    Route::post('/getDestination', [BookingController::class, 'fetchSpecificDestination']);
    Route::post('/overIssue', [BookingController::class, 'fetchSpecificOverIssueSeat']);
    Route::post('/overIssueAdd', [BookingController::class, 'overIssueAddNew']);
    Route::post('/advance', [BookingController::class, 'advanceData']);
    Route::post('/canceling', [BookingController::class, 'cancelingBooking']);
    Route::post('/elt', [BookingController::class, 'bookingElt']);
    Route::post('/getPassenger', [BookingController::class, 'getPassengersList']);

    // Schedule Closing
    Route::group(['prefix' => '/schedule', [CustomMiddleware::class]], function () {
        Route::post('/fetch', [ScheduleClosingController::class, 'fetchSchedule']);

        Route::group(['prefix' => '/closing', [CustomMiddleware::class]], function () {
            Route::post('/', [ScheduleClosingController::class, 'index']);
            Route::post('/store', [ScheduleClosingController::class, 'store']);
        });
    });

});
//pdf Ticket
Route::get('print/{id}/pdf', [BookingController::class, 'pdf'])->middleware(CustomMiddleware::class);
Route::get('print/{id}//pdf/passenger/list', [BookingController::class, 'passengerListPdf'])->middleware(CustomMiddleware::class);
Route::get('print/{id}/pdf/duplicate', [BookingController::class, 'duplicatePdf'])->middleware(CustomMiddleware::class);


Route::group(['prefix' => 'allBooking', [CustomMiddleware::class]], function () {
    Route::post('/routes', [AllBookingController::class, 'routes']);
    Route::post('/terminals', [AllBookingController::class, 'terminals']);
    Route::post('/buses', [AllBookingController::class, 'buses']);
});


Route::group(['prefix' => 'hrm/employee', [CustomMiddleware::class]], function () {
    Route::post('/', [EmployeeController::class, 'index']);
    Route::post('/store', [EmployeeController::class, 'store']);
    Route::post('/update', [EmployeeController::class, 'update']);
    Route::post('/delete', [EmployeeController::class, 'delete']);
    // Route::post('/logo-upload', [EmployeeController::class, 'logoUpload']);
});

Route::group(['prefix' => 'hrm/leave', [CustomMiddleware::class]], function () {
    Route::post('/', [LeaveController::class, 'index']);
    Route::post('/store', [LeaveController::class, 'store']);
    Route::post('/update', [LeaveController::class, 'update']);
    Route::post('/delete', [LeaveController::class, 'delete']);
    Route::post('/approval', [LeaveController::class, 'approval']);
});

Route::group(['prefix' => 'hrm/department', [CustomMiddleware::class]], function () {
    Route::post('/', [DepartmentController::class, 'index']);
    Route::post('/store', [DepartmentController::class, 'store']);
    Route::post('/update', [DepartmentController::class, 'update']);
    Route::post('/delete', [DepartmentController::class, 'delete']);
});

Route::group(['prefix' => 'hrm/designation', [CustomMiddleware::class]], function () {
    Route::post('/', [DesignationController::class, 'index']);
    Route::post('/store', [DesignationController::class, 'store']);
    Route::post('/edit', [DesignationController::class, 'edit']);
    Route::post('/update', [DesignationController::class, 'update']);
    Route::post('/delete', [DesignationController::class, 'delete']);
    Route::post('/selective', [DesignationController::class, 'selective']);
});

Route::group(['prefix' => 'settings/tickets', [CustomMiddleware::class]], function () {
    Route::post('/', [TicketsTemplateController::class, 'index']);
    Route::post('/store', [TicketsTemplateController::class, 'store']);
    Route::post('/update', [TicketsTemplateController::class, 'update']);
    Route::post('/delete', [TicketsTemplateController::class, 'delete']);
    Route::post('/terminals', [TicketsTemplateController::class, 'allTerminals']);
});

Route::get('/{any}', [AuthController::class, 'index'])->where('any', '.*');
