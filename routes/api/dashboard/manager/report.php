<?php

\Illuminate\Support\Facades\Route::middleware('role:manager')->get(
    '/revenue',
    [\App\Http\Controllers\Api\Manager\ReportController::class, 'index']
)->name('revenue');

\Illuminate\Support\Facades\Route::middleware('role:manager,ceo')
    ->prefix('branches/{branchId}/revenue-report')
    ->name('branches.revenue-report.')
    ->controller(\App\Http\Controllers\Api\Branch\BranchRevenueReportController::class)
    ->group(function () {
        \Illuminate\Support\Facades\Route::get('/', 'index')->name('index');
        \Illuminate\Support\Facades\Route::get('/target-progress', 'targetProgress')->name('target-progress');
        \Illuminate\Support\Facades\Route::get('/revenue-comparison', 'revenueComparison')->name('revenue-comparison');
        \Illuminate\Support\Facades\Route::get('/service-mix', 'serviceMix')->name('service-mix');
        \Illuminate\Support\Facades\Route::get('/employee-performance', 'employeePerformance')->name('employee-performance');
        \Illuminate\Support\Facades\Route::get('/customer-retention', 'customerRetention')->name('customer-retention');
    });
