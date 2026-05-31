<?php

\Illuminate\Support\Facades\Route::prefix('services')
    ->name('services.')
    ->group(function () {
        \Illuminate\Support\Facades\Route::get(
            '/analytics/kpi',
            [\App\Http\Controllers\Api\Ceo\ServiceAnalyticsController::class, 'kpi']
        )->name('analytics.kpi');

        \Illuminate\Support\Facades\Route::get(
            '/analytics',
            [\App\Http\Controllers\Api\Ceo\ServiceAnalyticsController::class, 'index']
        )->name('analytics');

        \Illuminate\Support\Facades\Route::get(
            '/summary',
            [\App\Http\Controllers\Api\Ceo\ServiceController::class, 'summary']
        )->name('summary');

        \Illuminate\Support\Facades\Route::get(
            '/highest-revenue',
            [\App\Http\Controllers\Api\Ceo\ServiceController::class, 'highestRevenue']
        )->name('highest-revenue');

        \Illuminate\Support\Facades\Route::get(
            '/lowest-revenue',
            [\App\Http\Controllers\Api\Ceo\ServiceController::class, 'lowestRevenue']
        )->name('lowest-revenue');

        \Illuminate\Support\Facades\Route::get(
            '/revenue-analysis',
            [\App\Http\Controllers\Api\Ceo\ServiceController::class, 'index']
        )->name('revenue-analysis');

        \Illuminate\Support\Facades\Route::get(
            '/',
            [\App\Http\Controllers\Api\Ceo\ServiceController::class, 'catalog']
        )->name('index');
    });
