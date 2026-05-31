<?php

\Illuminate\Support\Facades\Route::middleware('role:manager')->get(
    '/services',
    [\App\Http\Controllers\Api\Manager\ServiceController::class, 'index']
)->name('services');

\Illuminate\Support\Facades\Route::middleware('role:manager,ceo')
    ->prefix('branches/{branchId}/services')
    ->name('branches.services.')
    ->controller(\App\Http\Controllers\Api\Branch\BranchServiceManagementController::class)
    ->group(function () {
        \Illuminate\Support\Facades\Route::get('/management', 'index')->name('management.index');
        \Illuminate\Support\Facades\Route::get('/management/kpi', 'kpi')->name('management.kpi');
        \Illuminate\Support\Facades\Route::get('/', 'services')->name('index');
        \Illuminate\Support\Facades\Route::patch('/{serviceId}/website-visibility', 'updateWebsiteVisibility')->name('website-visibility.update');
        \Illuminate\Support\Facades\Route::patch('/{serviceId}/emergency-lock', 'updateEmergencyLock')->name('emergency-lock.update');
        \Illuminate\Support\Facades\Route::patch('/{serviceId}/price-override', 'updatePriceOverride')->name('price-override.update');
    });
