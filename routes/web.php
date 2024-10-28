<?php

use Illuminate\Support\Facades\Route;
use App\Models\Schedule\Schedule;
use Illuminate\Support\Facades\DB;
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
Route::get('/test-view', function(){
    return view('test-view');
});

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', function () {

    DB::enableQueryLog();
    $schedules = Schedule::with(['scheduleDetail' => function($q) {
        $q->orderBy("schedule_date", "DESC")->limit(10);
    }])
    ->get();
    return DB::getQueryLog();
});
