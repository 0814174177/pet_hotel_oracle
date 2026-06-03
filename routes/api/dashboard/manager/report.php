<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Manager\ReportController;
use App\Http\Controllers\Api\Manager\ReportManagementController;

// Báo cáo doanh thu chuỗi (Dành riêng cho Manager)
Route::middleware('role:manager')
    ->get('/revenue', [ReportController::class, 'index'])
    ->name('revenue');

// Báo cáo doanh thu chi nhánh (Dành cho Manager và CEO)
Route::middleware('role:manager,ceo')
    ->prefix('branches/{branchId}/revenue-report')
    ->name('branches.revenue-report.')
    ->controller(ReportManagementController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/target-progress', 'targetProgress')->name('target-progress');
        Route::get('/revenue-comparison', 'revenueComparison')->name('revenue-comparison');
        Route::get('/service-mix', 'serviceMix')->name('service-mix');
        Route::get('/aov', 'aovSummary')->name('aov');
        Route::get('/employee-performance', 'employeePerformance')->name('employee-performance');
        Route::get('/customer-retention', 'customerRetention')->name('customer-retention');
    });
