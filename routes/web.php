<?php

use App\Http\Controllers\AuthController;
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
//Route::get('/', function () {
//    return 'Hello World';
//});
//Reset Password Route
require_once('web/reset_password.php');
//Auth Route
require_once('web/auth.php');
//Maintenance Route
require_once('web/maintenance.php');
//Refreshment Route
require_once('web/refreshment.php');
//Role Routes
require_once('web/role.php');
//company Routes
require_once('web/company.php');
//users Routes
require_once('web/users.php');
//Terminals Route
require_once('web/terminals.php');
//cities Route
require_once('web/cities.php');
// FareTable Route
require_once('web/fareTable.php');
// FareTable Route
require_once('web/terminalTimeDifference.php');
//fare Class  Route
require_once('web/fareClass.php');
// Discount Route
require_once('web/discount.php');
// Surcharge Route
require_once('web/surcharge.php');
//Schedule Route
require_once('web/schedule.php');
//Buses Route
require_once('web/buses.php');
//BusClasses Route
require_once('web/busClass.php');
//Booking Route
require_once('web/booking.php');
//pdf Ticket Route
require_once('web/pdf.php');
//AllBooking Route
require_once('web/allBooking.php');
//Hrm Employees Route
require_once('web/hrm/employees.php');
//Hrm Leave Route
require_once('web/hrm/leave.php');
//Hrm Departments Route
require_once('web/hrm/departments.php');
//Hrm Designation Route
require_once('web/hrm/designation.php');
//Hrm Tickets Route
require_once('web/hrm/tickets.php');
//Account
require_once('web/accounts.php');
// Profile Routes
require_once('web/profile/profile.php');
// Expenses Routes
require_once('web/expenses.php');

Route::get('/{any}', [AuthController::class, 'index'])->where('any', '.*');
