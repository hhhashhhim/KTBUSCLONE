<?php

use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\Discount\DiscountController;
use App\Http\Controllers\FareTableController;
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

Route::post("/login",[AuthController::class,'login']);
Route::post("/double-check",[AuthController::class,'doubleCheck']);
Route::get("/logout",[AuthController::class,'logout'])->middleware([CustomMiddleware::class]);


// Route::group(['prefix'=>'admin'],function(){
//     Route::get('dashboard',[TestController::class,'index'])->name('dashboard');
//     Route::get('company/add',[TestController::class,'add_company'])->name('company.add');
//     Route::get('company/all',[TestController::class,'all_companies'])->name('company.all');
// });



Route::group(['prefix'=>'role','middleware',[CustomMiddleware::class]],function(){
    Route::post('/',[RoleController::class,'index']);
    Route::post('store',[RoleController::class,'store']);
    Route::post('update',[RoleController::class,'update']);
    Route::post('delete',[RoleController::class,'delete']);
    Route::post('/get',[RoleController::class,'role']);
});
Route::group(['prefix'=>'company','middleware',[CustomMiddleware::class]],function(){
    Route::post('/',[CompanyController::class,'index']);
    Route::post('store',[CompanyController::class,'store']);
    Route::post('logo-upload',[CompanyController::class,'logoUpload']);
    Route::post('update',[CompanyController::class,'update']);
    Route::post('delete',[CompanyController::class,'delete']);
    Route::post('/get',[CompanyController::class,'role']);
    Route::post('/roles',[CompanyController::class,'company_roles']);
    Route::post('/get',[CompanyController::class,'company']);
});
Route::group(['prefix'=>'user',[CustomMiddleware::class]],function(){
    Route::post('/',[UserController::class,'index']);
    Route::post('store',[UserController::class,'store']);
    Route::post('update',[UserController::class,'update']);
    Route::post('delete',[UserController::class,'delete']);
    Route::post('permissions',[UserController::class,'permissions']);
});
Route::group(['prefix'=>'terminal',[CustomMiddleware::class]],function(){
    Route::post('/',[TerminalController::class,'index']);
    Route::post('store',[TerminalController::class,'store']);
    Route::post('update',[TerminalController::class,'update']);
    Route::post('delete',[TerminalController::class,'delete']);
    Route::post('permissions',[TerminalController::class,'permissions']);
});
Route::group(['prefix'=>'city',[CustomMiddleware::class]],function(){
    Route::post('/',[CityController::class,'index']);
    Route::post('store',[CityController::class,'store']);
    Route::post('update',[CityController::class,'update']);
    Route::post('delete',[CityController::class,'delete']);
});

Route::group(['prefix'=>'cities',[CustomMiddleware::class]],function(){
    Route::post('/terminals',[CityController::class,'cityTerminals']);
    Route::post('/routes',[CityController::class,'cityRoutes']);
    Route::post('/routes/list',[CityController::class,'city_routes_list']);
    Route::post('/routes/details',[CityController::class,'city_routes_details']);

});

Route::group(['prefix'=>'fare-table',[CustomMiddleware::class]],function(){

    Route::post('/',[FareTableController::class,'record']);
    Route::post('/store',[FareTableController::class,'store']);
});

Route::group(['prefix'=>'discount',[CustomMiddleware::class]],function(){
    Route::post('/',[DiscountController::class,'index']);
    Route::post('/store',[DiscountController::class,'storeDiscount']);
    Route::post('/update',[DiscountController::class,'updateDiscount']);
    Route::post('/delete',[DiscountController::class,'deleteDiscount']);
});

Route::group(['prefix'=>'surcharge',[CustomMiddleware::class]],function(){
    Route::post('/',[SurchargeController::class,'index']);
    Route::post('/store',[SurchargeController::class,'storeSurcharge']);
    Route::post('/update',[SurchargeController::class,'updateSurcharge']);
    Route::post('/delete',[SurchargeController::class,'deleteSurcharge']);
});
Route::get('/{any}', [AuthController::class,'index'])->where('any', '.*');
