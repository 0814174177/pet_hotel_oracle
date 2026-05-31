<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Ceo\DashboardController;

Route::controller(DashboardController::class)->group(function () {
    Route::get('/current-hotel-occupancy', 'currentHotelOccupancy')->name('current-hotel-occupancy');
    Route::get('/occupancy-rate', 'occupancyRate')->name('occupancy-rate');
    Route::get('/revpar', 'revpar')->name('revpar');
    Route::get('/customer-trend', 'customerTrend')->name('customer-trend');
    Route::get('/revenue', 'chainRevenue')->name('revenue');
    Route::get('/estimated-cogs', 'estimatedCogs')->name('estimated-cogs');
    Route::get('/cost-structure', 'costStructure')->name('cost-structure');
    Route::get('/revenue-mix', 'revenueMix')->name('revenue-mix');
    Route::get('/revenue-and-cogs-trend', 'revenueAndCogsTrend')->name('revenue-and-cogs-trend');
    Route::get('/branch-revenue', 'branchRevenue')->name('branch-revenue');
    Route::get('/branch-ranking', 'branchRanking')->name('branch-ranking');
    Route::get('/top-used-services', 'topUsedServices')->name('top-used-services');
    Route::get('/risk-alerts', 'riskAlerts')->name('risk-alerts');
});
