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
Route::get('/finance/cost-structure', [FinanceController::class, 'costStructure'])
    ->name('finance.cost-structure');
Route::get('/finance/branch-estimated-profit', [FinanceController::class, 'branchEstimatedProfit'])
    ->name('finance.branch-estimated-profit');
Route::get('/finance/service-estimated-profit', [FinanceController::class, 'serviceEstimatedProfit'])
    ->name('finance.service-estimated-profit');
Route::get('/finance/lowest-margin-services', [FinanceController::class, 'lowestMarginServices'])
    ->name('finance.lowest-margin-services');
Route::get('/finance/negative-branch-profit-alerts', [FinanceController::class, 'negativeBranchProfitAlerts'])
    ->name('finance.negative-branch-profit-alerts');
Route::get('/finance/low-service-margin-alerts', [FinanceController::class, 'lowServiceMarginAlerts'])
    ->name('finance.low-service-margin-alerts');
Route::get('/finance/cost-growth-alerts', [FinanceController::class, 'costGrowthAlerts'])
    ->name('finance.cost-growth-alerts');
