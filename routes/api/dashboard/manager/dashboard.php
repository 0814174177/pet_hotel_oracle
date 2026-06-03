<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Manager\DashboardController;

Route::middleware('role:manager')->get('/overview', [DashboardController::class, 'overview'])->name('overview');
Route::middleware('role:manager')->get('/inventory-warning', [DashboardController::class, 'inventoryWarning'])->name('inventory-warning');
Route::middleware('role:manager')->get('/health-warning', [DashboardController::class, 'healthWarning'])->name('health-warning');
Route::middleware('role:manager')->get('/financial-risk-warning', [DashboardController::class, 'financialRiskWarning'])->name('financial-risk-warning');
Route::middleware('role:manager')->get('/late-cancelled-bookings', [DashboardController::class, 'lateCancelledBookings'])->name('late-cancelled-bookings');
Route::middleware('role:manager')->get('/top-revenue-services', [DashboardController::class, 'topRevenueServices'])->name('top-revenue-services');
Route::middleware('role:manager')->get('/revenue-structure', [DashboardController::class, 'revenueStructure'])->name('revenue-structure');
Route::middleware('role:manager')->get('/unpaid-invoices', [DashboardController::class, 'unpaidInvoices'])->name('unpaid-invoices');
Route::middleware('role:manager')
    ->get('/branches/{branchId}/overview', [DashboardController::class, 'branchOverview'])
    ->name('branches.overview');
Route::middleware('role:manager')
    ->get('/branches/{branchId}/inventory-warning', [DashboardController::class, 'branchInventoryWarning'])
    ->name('branches.inventory-warning');
Route::middleware('role:manager')
    ->get('/branches/{branchId}/health-warning', [DashboardController::class, 'branchHealthWarning'])
    ->name('branches.health-warning');
Route::middleware('role:manager')
    ->get('/branches/{branchId}/financial-risk-warning', [DashboardController::class, 'branchFinancialRiskWarning'])
    ->name('branches.financial-risk-warning');
Route::middleware('role:manager')
    ->get('/branches/{branchId}/late-cancelled-bookings', [DashboardController::class, 'branchLateCancelledBookings'])
    ->name('branches.late-cancelled-bookings');
Route::middleware('role:manager')
    ->get('/branches/{branchId}/top-revenue-services', [DashboardController::class, 'branchTopRevenueServices'])
    ->name('branches.top-revenue-services');
Route::middleware('role:manager')
    ->get('/branches/{branchId}/revenue-structure', [DashboardController::class, 'branchRevenueStructure'])
    ->name('branches.revenue-structure');
Route::middleware('role:manager')
    ->get('/branches/{branchId}/unpaid-invoices', [DashboardController::class, 'branchUnpaidInvoices'])
    ->name('branches.unpaid-invoices');
