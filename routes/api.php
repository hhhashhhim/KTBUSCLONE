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

require_once('api/reset_password.php');
//Auth Route
require_once('api/auth.php');
//Maintenance Route
require_once('api/maintenance.php');
//Refreshment Route
require_once('api/refreshment.php');
//Role Routes
require_once('api/role.php');
//company Routes
require_once('api/company.php');
// Routes Panel Routes
require_once('api/routes.php');
//users Routes
require_once('api/users.php');
//Terminals Route
require_once('api/terminals.php');
//cities Route
require_once('api/cities.php');
// FareTable Route
require_once('api/fareTable.php');
// FareTable Route
require_once('api/terminalTimeDifference.php');
//fare Class  Route
require_once('api/fareClass.php');
// Discount Route
require_once('api/discount.php');
// Surcharge Route
require_once('api/surcharge.php');
//Schedule Route
require_once('api/schedule.php');
//Buses Route
require_once('api/buses.php');
//BusClasses Route
require_once('api/busClass.php');
//Booking Route
require_once('api/booking.php');
//pdf Ticket Route
require_once('api/pdf.php');
//AllBooking Route
require_once('api/allBooking.php');
//Hrm Employees Route
require_once('api/hrm/employees.php');
//Hrm Leave Route
require_once('api/hrm/leave.php');
//Hrm Departments Route
require_once('api/hrm/departments.php');
//Hrm Designation Route
require_once('api/hrm/designation.php');
//Hrm Tickets Route
require_once('api/hrm/tickets.php');
//Account
require_once('api/accounts.php');
// Profile Routes
require_once('api/profile/profile.php');
// Expenses Routes
require_once('api/expenses.php');
// Loyalty Card Routes
require_once('api/card/loyaltyCard.php');
// Loyalty Card Assign  Routes
require_once('api/card/loyaltyCardAssign.php');
// Reports Header Routes
require_once('api/reports/reportsHeader.php');
// Reports Schedule Drop
require_once('api/reports/scheduleDropReport.php');
// Reports Routes
require_once('api/reports/reports.php');
// Reports Advance sale Routes
require_once('api/reports/advanceSalesReports.php');
// Reports Confirm Cancellation Routes
require_once('api/reports/confirmCancellationReports.php');
