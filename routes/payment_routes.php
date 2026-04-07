<?php

use App\Http\Controllers\PaymentReceiptController;
use App\Http\Controllers\DunningController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 入金消込・債権管理ルート
| インボイス対応類型「決済機能」対応
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->prefix('payments')->name('payments.')->group(function () {

    // 入金消込ダッシュボード
    Route::get('/', [PaymentReceiptController::class, 'index'])->name('index');

    // 入金登録
    Route::get('/create/{invoice}', [PaymentReceiptController::class, 'create'])->name('create');
    Route::post('/', [PaymentReceiptController::class, 'store'])->name('store');
    Route::get('/{paymentReceipt}', [PaymentReceiptController::class, 'show'])->name('show');

    // 消込取り消し
    Route::delete('/matching/{matching}/reverse', [PaymentReceiptController::class, 'reverse'])
         ->name('matching.reverse');

    // API エンドポイント
    Route::get('/api/summary', [PaymentReceiptController::class, 'summary'])->name('api.summary');
    Route::get('/api/overdue', [PaymentReceiptController::class, 'overdueList'])->name('api.overdue');
    Route::get('/api/report', [PaymentReceiptController::class, 'monthlyReport'])->name('api.report');

    // 督促管理
    Route::prefix('dunning')->name('dunning.')->group(function () {
        Route::get('/', [DunningController::class, 'index'])->name('index');
        Route::post('/invoices/{invoice}', [DunningController::class, 'store'])->name('store');
        Route::patch('/{dunningRecord}/result', [DunningController::class, 'updateResult'])->name('update-result');
        Route::get('/schedules', [DunningController::class, 'schedules'])->name('schedules');
        Route::post('/schedules', [DunningController::class, 'saveSchedule'])->name('schedules.save');
    });
});

// 請求書からの直接督促（既存の invoices ルートへのショートカット）
Route::post('/invoices/{invoice}/dunning', [DunningController::class, 'store'])
     ->middleware(['auth'])
     ->name('invoices.dunning');
