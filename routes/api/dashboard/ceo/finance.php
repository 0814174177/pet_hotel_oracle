<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Ceo\FinanceController;

Route::get('/finance', [FinanceController::class, 'index'])->name('finance');
Route::get('/finance/total-chain-revenue', [FinanceController::class, 'totalChainRevenue'])
    ->name('finance.total-chain-revenue');
Route::get('/finance/estimated-total-cost', [FinanceController::class, 'estimatedTotalCost'])
    ->name('finance.estimated-total-cost');
Route::get('/finance/estimated-profit', [FinanceController::class, 'estimatedProfit'])
    ->name('finance.estimated-profit');
Route::get('/finance/estimated-margin', [FinanceController::class, 'estimatedMargin'])
    ->name('finance.estimated-margin');
Route::get('/finance/trend', [FinanceController::class, 'trend'])
    ->name('finance.trend');
Route::get('/finance/monthly-trend', [FinanceController::class, 'monthlyTrend'])
    ->name('finance.monthly-trend');
