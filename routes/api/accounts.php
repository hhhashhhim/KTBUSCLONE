<?php

use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Account\AccountHeadController;
use App\Http\Controllers\Account\AccountClosingController;
use App\Http\Controllers\Account\Pdf\TransactionPdfController;
use App\Http\Controllers\Account\report\FinanceReportController;
use App\Http\Controllers\Account\BankTransactionController;
use App\Http\Controllers\Account\CashTransactionController;
use App\Http\Controllers\Account\JournalTransactionController;
use App\Http\Middleware\CustomMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'web/v1/accounts','middleware' => ['auth:sanctum']], function () {

    Route::post('/closing/update', [AccountClosingController::class, 'accountClosingUpdate']);

    Route::get('/{first}/second', [AccountController::class,'secondLevelOfFirst']);

        Route::group(['prefix' => '/groups', [CustomMiddleware::class]], function () {
            Route::post('/', [AccountController::class, 'accountGroups']);
            Route::post('/second', [AccountController::class, 'getThirdLevel']);
            Route::get('/{second}/third', [AccountController::class,'thirdLevelOfSecond']);
            Route::get('/{third}/fourth', [AccountController::class,'fourthLevelOfThird']);
            Route::post('/store', [AccountController::class, 'groupStore']);
            Route::post('/update', [AccountController::class, 'groupUpdate']);
        });

        Route::group(['prefix' => '/heads', [CustomMiddleware::class]], function () {
            Route::post('/add', [AccountHeadController::class,'headStore']);
            Route::get('/', [AccountHeadController::class,'accountHeads']);
            Route::post('/update', [AccountHeadController::class,'headUpdate']);

            Route::group(['prefix' => '/banks', [CustomMiddleware::class]], function () {
                Route::post('/add', [AccountHeadController::class,'headBankStore']);
                Route::get('/', [AccountHeadController::class,'accountHeadBanks']);
                Route::post('/update', [AccountHeadController::class,'headBankUpdate']);
            });
            
            Route::group(['prefix' => '/cash', [CustomMiddleware::class]], function () {
                Route::post('/add', [AccountHeadController::class,'headCashStore']);
                Route::get('/', [AccountHeadController::class,'accountHeadCash']);
                Route::post('/update', [AccountHeadController::class,'headCashUpdate']);
            });
        });

        Route::prefix('transactions')->group(function () {

            Route::prefix('bank-transactions')->group(function () {
                Route::post('/add', [BankTransactionController::class,'bankTransactionAdd']);
                Route::get('/', [BankTransactionController::class,'bankTransactions']);
                Route::post('/edit', [BankTransactionController::class,'bankTransaction']);
                Route::post('/show', [BankTransactionController::class,'bankTransactionDetail']);
                Route::post('/do/approve', [BankTransactionController::class,'approveBankTransaction']);
                Route::post('/update', [BankTransactionController::class,'bankTransactionUpdate']);
            });
            
            Route::prefix('cash-transactions')->group(function () {
                Route::post('/add', [CashTransactionController::class,'cashTransactionAdd']);
                Route::get('/', [CashTransactionController::class,'cashTransactions']);
                Route::post('/edit', [CashTransactionController::class,'cashTransaction']);
                Route::post('/show', [CashTransactionController::class,'cashTransactionDetail']);
                Route::post('/do/approve', [CashTransactionController::class,'approveCashTransaction']);
                Route::post('/update', [CashTransactionController::class,'cashTransactionUpdate']);
            });
            
            Route::prefix('journal-transactions')->group(function () {
                Route::post('/add', [JournalTransactionController::class,'journalTransactionAdd']);
                Route::get('/', [JournalTransactionController::class,'journalTransactions']);
                Route::post('/edit', [JournalTransactionController::class,'journalTransaction']);
                Route::post('/show', [JournalTransactionController::class,'journalTransactionDetail']);
                Route::post('/do/approve', [JournalTransactionController::class,'approveJournalTransaction']);
                Route::post('/update', [JournalTransactionController::class,'journalTransactionUpdate']);
            });

        });

        Route::prefix('reports')->group(function () {
            Route::get('/helper/data', [FinanceReportController::class,'helperData']);
            Route::prefix('finance')->group(function () {
                Route::post('/receipts', [FinanceReportController::class,'receiptReport']);
                Route::post('/general/ledger', [FinanceReportController::class,'generalLedgerReport']);
                Route::post('/ledger', [FinanceReportController::class,'ledgerReport']);
                Route::post('/general/journal', [FinanceReportController::class,'journalReport']);
                Route::post('/trial/sheet', [FinanceReportController::class,'trialSheetReport']);
                Route::post('/daily/report', [FinanceReportController::class,'dailyReport']);
            });
        });
});
