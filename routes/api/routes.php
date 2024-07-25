<?php


use App\Http\Controllers\RouteController;
use App\Http\Controllers\SubRouteController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;


Route::group(['prefix' => 'web/v1/routes','middleware' => ['auth:sanctum']], function () {
    Route::post('/', [RouteController::class, 'index']);
    Route::post('/store', [RouteController::class, 'store']);
    Route::post('/edit', [RouteController::class, 'edit']);
    Route::post('/update', [RouteController::class, 'update']);
    Route::post('/list', [RouteController::class, 'list']);
    Route::post('/details', [RouteController::class, 'details']);
    Route::post('/hide', [RouteController::class, 'hideRoute']);
    
    Route::group(['prefix' => '/visibilities'], function () {
        Route::post('/', [RouteController::class, 'routeVisibilities']);
        Route::post('/update', [RouteController::class, 'visibilityUpdate']);
    
    });

    Route::group(['prefix' => '/sub-routes'], function () {
        Route::post('/', [SubRouteController::class, 'index']);
        Route::post('/store', [SubRouteController::class, 'store']);
        Route::post('/update', [SubRouteController::class, 'update']);
    
    });

});

?>
