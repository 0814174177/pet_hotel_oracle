<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Ceo\ServiceAnalyticsController;
use App\Http\Controllers\Api\Ceo\ServiceController;

Route::prefix('services')
    ->name('services.')
    ->group(function () {
        
        // Nhóm định tuyến Phân tích dữ liệu (Analytics)
        Route::controller(ServiceAnalyticsController::class)->group(function () {
            Route::get('/analytics/kpi', 'kpi')->name('analytics.kpi');
            Route::get('/analytics', 'index')->name('analytics');
        });

        // Nhóm định tuyến Quản lý dịch vụ (Service)
        Route::controller(ServiceController::class)->group(function () {
            Route::get('/summary', 'summary')->name('summary');
            Route::get('/highest-revenue', 'highestRevenue')->name('highest-revenue');
            Route::get('/lowest-revenue', 'lowestRevenue')->name('lowest-revenue');
            Route::get('/revenue-analysis', 'index')->name('revenue-analysis');
            Route::get('/', 'catalog')->name('index');
        });

    });