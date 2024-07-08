<?php

use App\Http\Controllers\Account\AccountController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'web/v1/accounts','middleware' => ['auth:sanctum']], function () {
    Route::post('/coa/groups', [AccountController::class, 'accountGroups']);
    Route::post('/coa/second/groups', [AccountController::class, 'getThirdLevel']);
    Route::post('/coa/group/store', [AccountController::class, 'groupStore']);
    Route::post('/coa/category/update', [AccountController::class, 'categoryUpdate']);
});
