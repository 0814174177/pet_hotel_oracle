<?php

use App\Http\Controllers\Api\Ceo\BranchController;
use App\Http\Controllers\Api\Ceo\DashboardController;
use App\Http\Controllers\Api\Ceo\FinanceController;
use App\Http\Controllers\Api\Ceo\ServiceAnalyticsController;
use App\Http\Controllers\Api\Ceo\ServiceController;
use App\Http\Controllers\Api\Ceo\VendorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:ceo'])
    ->prefix('ceo')
    ->name('api.ceo.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'debugSummary'])->name('dashboard');
        Route::prefix('dashboard')
            ->name('dashboard.')
            ->controller(DashboardController::class)
            ->group(function () {
                Route::get('/current-hotel-occupancy', 'currentHotelOccupancy')->name('current-hotel-occupancy');
                Route::get('/occupancy-rate', 'occupancyRate')->name('occupancy-rate');
                Route::get('/revpar', 'revpar')->name('revpar');
                Route::get('/customer-trend', 'customerTrend')->name('customer-trend');
                Route::get('/total-revenue', 'chainRevenue')->name('total-revenue');
                Route::get('/chain-revenue', 'chainRevenue')->name('chain-revenue');
                Route::get('/estimated-cogs', 'estimatedCogs')->name('estimated-cogs');
                Route::get('/total-cost', 'estimatedCogs')->name('total-cost');
                Route::get('/inventory-import-cost', 'estimatedCogs')->name('inventory-import-cost');
                Route::get('/service-ratio', 'revenueMix')->name('service-ratio');
                Route::get('/revenue-mix', 'revenueMix')->name('revenue-mix');
                Route::get('/revenue-and-cogs-trend', 'revenueAndCogsTrend')->name('revenue-and-cogs-trend');
                Route::get('/revenue-trend', 'revenueAndCogsTrend')->name('revenue-trend');
                Route::get('/branch-revenue', 'branchRevenue')->name('branch-revenue');
                Route::get('/branch-ranking', 'branchRanking')->name('branch-ranking');
                Route::get('/top-used-services', 'topUsedServices')->name('top-used-services');
                Route::get('/risk-alerts', 'riskAlerts')->name('risk-alerts');
                Route::get('/debug-summary', 'debugSummary')->name('debug-summary');
            });
        Route::get('/services/analytics/kpi', [ServiceAnalyticsController::class, 'kpi'])->name('services.analytics.kpi');
        Route::get('/services/analytics', [ServiceAnalyticsController::class, 'index'])->name('services.analytics');
        Route::get('/services/summary', [ServiceController::class, 'summary'])->name('services.summary');
        Route::get('/services/highest-revenue', [ServiceController::class, 'highestRevenue'])->name('services.highest-revenue');
        Route::get('/services/lowest-revenue', [ServiceController::class, 'lowestRevenue'])->name('services.lowest-revenue');
        Route::get('/services/revenue-analysis', [ServiceController::class, 'index'])->name('services.revenue-analysis');
        Route::get('/services', [ServiceController::class, 'catalog'])->name('services');
        Route::get('/branches/active-count', [BranchController::class, 'activeCount'])->name('branches.active-count');
        Route::get('/branches/highest-revenue', [BranchController::class, 'highestRevenue'])->name('branches.highest-revenue');
        Route::get('/branches/lowest-occupancy', [BranchController::class, 'lowestOccupancy'])->name('branches.lowest-occupancy');
        Route::get('/branches', [BranchController::class, 'index'])->name('branches');
        Route::get('/vendors', [VendorController::class, 'index'])->name('vendors');
        Route::get('/finance', [FinanceController::class, 'index'])->name('finance');
    });
