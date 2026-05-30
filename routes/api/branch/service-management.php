<?php

use App\Http\Controllers\Api\Branch\BranchServiceManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:manager,ceo'])
    ->prefix('branches/{branchId}/services')
    ->name('api.branches.services.')
    ->controller(BranchServiceManagementController::class)
    ->group(function () {
        // TODO: Add branch membership and action-specific authorization middleware or policies when available.
        Route::get('/management', 'index')->name('management.index');
        Route::get('/management/kpi', 'kpi')->name('management.kpi');
        Route::get('/', 'services')->name('index');
        Route::patch('/{serviceId}/website-visibility', 'updateWebsiteVisibility')->name('website-visibility.update');
        Route::patch('/{serviceId}/emergency-lock', 'updateEmergencyLock')->name('emergency-lock.update');
        Route::patch('/{serviceId}/price-override', 'updatePriceOverride')->name('price-override.update');
    });
