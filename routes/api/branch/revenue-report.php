<?php

use App\Http\Controllers\Api\Branch\BranchRevenueReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:manager,ceo'])
    ->prefix('branches/{branchId}/revenue-report')
    ->name('api.branches.revenue-report.')
    ->controller(BranchRevenueReportController::class)
    ->group(function () {
        // TODO: Add branch membership and report-view authorization middleware or policies when available.
        Route::get('/', 'index')->name('index');
        Route::get('/target-progress', 'targetProgress')->name('target-progress');
        Route::get('/revenue-comparison', 'revenueComparison')->name('revenue-comparison');
        Route::get('/service-mix', 'serviceMix')->name('service-mix');
        Route::get('/employee-performance', 'employeePerformance')->name('employee-performance');
        Route::get('/customer-retention', 'customerRetention')->name('customer-retention');
    });
