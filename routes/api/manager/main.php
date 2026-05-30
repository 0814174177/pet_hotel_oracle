<?php

use App\Http\Controllers\Api\Manager\DashboardController;
use App\Http\Controllers\Api\Manager\InventoryController;
use App\Http\Controllers\Api\Manager\ReportController;
use App\Http\Controllers\Api\Manager\ServiceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:manager'])
    ->prefix('manager')
    ->name('api.manager.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/overview', [DashboardController::class, 'overview'])->name('dashboard.overview');
        Route::get('/services', [ServiceController::class, 'index'])->name('services');
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory');
        Route::get('/reports', [ReportController::class, 'index'])->name('reports');
    });
