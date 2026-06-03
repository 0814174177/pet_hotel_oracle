<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Manager\ServiceController;
use App\Http\Controllers\Api\Branch\BranchServiceManagementController;

// Danh sách dịch vụ tổng (Dành riêng cho Manager)
Route::middleware('role:manager')
    ->get('/services', [ServiceController::class, 'index'])
    ->name('services');

// Quản lý dịch vụ tại chi nhánh (Dành cho Manager và CEO)
Route::middleware('role:manager,ceo')
    ->prefix('branches/{branchId}/services')
    ->name('branches.services.')
    ->controller(BranchServiceManagementController::class)
    ->group(function () {
        Route::get('/management', 'index')->name('management.index');
        Route::get('/management/kpi', 'kpi')->name('management.kpi');
        Route::get('/revenue-progress', 'revenueProgress')->name('revenue-progress');
        Route::get('/revenue-drop-alerts', 'revenueDropAlerts')->name('revenue-drop-alerts');
        Route::get('/upsell-rate', 'upsellRate')->name('upsell-rate');
        Route::get('/filtered/{search}/{serviceGroup}/{status}', 'services')->name('filtered-list');
        Route::get('/', 'services')->name('index');
        Route::patch('/{serviceId}/website-visibility', 'updateWebsiteVisibility')->name('website-visibility.update');
        Route::patch('/{serviceId}/emergency-lock', 'updateEmergencyLock')->name('emergency-lock.update');
        Route::patch('/{serviceId}/price-override', 'updatePriceOverride')->name('price-override.update');
    });
