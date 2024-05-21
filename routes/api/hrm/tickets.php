<?php

use App\Http\Controllers\Setting\Tickets\TicketsTemplateController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'web/v1/settings','middleware' => ['auth:sanctum']], function () {
    Route::post('/activity/logs', [TicketsTemplateController::class, 'activityLog']);
    
    Route::group(['prefix' => '/tickets','middleware' => ['auth:sanctum']], function () {
        Route::post('/', [TicketsTemplateController::class, 'index']);
        Route::post('/store', [TicketsTemplateController::class, 'store']);
        Route::post('/update', [TicketsTemplateController::class, 'update']);
        Route::post('/delete', [TicketsTemplateController::class, 'delete']);
        Route::post('/terminals', [TicketsTemplateController::class, 'allTerminals']);
    });
});
