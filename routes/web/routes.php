<?php


use App\Http\Controllers\RouteController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;


Route::group(['prefix' => 'routes', [CustomMiddleware::class]], function () {
    Route::post('/', [RouteController::class, 'index']);
    Route::post('/store', [RouteController::class, 'store']);
    Route::post('/update', [RouteController::class, 'update']);
    Route::post('/list', [RouteController::class, 'list']);
    Route::post('/details', [RouteController::class, 'details']);
    Route::post('/hide', [RouteController::class, 'hideRoute']);
});

?>
