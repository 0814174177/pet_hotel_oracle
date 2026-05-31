<?php

\Illuminate\Support\Facades\Route::get(
    '/overview',
    [\App\Http\Controllers\Api\Ceo\DashboardController::class, 'debugSummary']
)->name('overview');

\Illuminate\Support\Facades\Route::controller(\App\Http\Controllers\Api\Ceo\DashboardController::class)
    ->group(function () {
        \Illuminate\Support\Facades\Route::get('/current-hotel-occupancy', 'currentHotelOccupancy')->name('current-hotel-occupancy');
        \Illuminate\Support\Facades\Route::get('/occupancy-rate', 'occupancyRate')->name('occupancy-rate');
        \Illuminate\Support\Facades\Route::get('/revpar', 'revpar')->name('revpar');
        \Illuminate\Support\Facades\Route::get('/customer-trend', 'customerTrend')->name('customer-trend');
        \Illuminate\Support\Facades\Route::get('/revenue', 'chainRevenue')->name('revenue');
        \Illuminate\Support\Facades\Route::get('/estimated-cogs', 'estimatedCogs')->name('estimated-cogs');
        \Illuminate\Support\Facades\Route::get('/revenue-mix', 'revenueMix')->name('revenue-mix');
        \Illuminate\Support\Facades\Route::get('/revenue-and-cogs-trend', 'revenueAndCogsTrend')->name('revenue-and-cogs-trend');
        \Illuminate\Support\Facades\Route::get('/branch-revenue', 'branchRevenue')->name('branch-revenue');
        \Illuminate\Support\Facades\Route::get('/branch-ranking', 'branchRanking')->name('branch-ranking');
        \Illuminate\Support\Facades\Route::get('/top-used-services', 'topUsedServices')->name('top-used-services');
        \Illuminate\Support\Facades\Route::get('/risk-alerts', 'riskAlerts')->name('risk-alerts');
        \Illuminate\Support\Facades\Route::get('/debug-summary', 'debugSummary')->name('debug-summary');
    });
