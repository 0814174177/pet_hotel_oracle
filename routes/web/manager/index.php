<?php

use App\Http\Controllers\Web\Manager\DashboardController;
use App\Http\Controllers\Web\Manager\EmployeeController;
use App\Http\Controllers\Web\Manager\InventoryController;
use App\Http\Controllers\Web\Manager\PromotionController;
use App\Http\Controllers\Web\Manager\ReportController;
use App\Http\Controllers\Web\Manager\ServiceController;
use App\Services\Manager\ManagerBranchScopeService;
use Illuminate\Support\Facades\Route;

Route::get('/', function (ManagerBranchScopeService $branchScope) {
    return redirect()->route('manager.branches.dashboard', [
        'branchId' => $branchScope->currentBranchId(),
    ]);
})->name('index');

Route::get('/dashboard', function (ManagerBranchScopeService $branchScope) {
    return redirect()->route('manager.branches.dashboard', [
        'branchId' => $branchScope->currentBranchId(),
    ]);
})->name('dashboard');

Route::get('/services', function (ManagerBranchScopeService $branchScope) {
    return redirect()->route('manager.branches.service', [
        'branchId' => $branchScope->currentBranchId(),
    ]);
})->name('service');

Route::get('/reports', function (ManagerBranchScopeService $branchScope) {
    return redirect()->route('manager.branches.reports', [
        'branchId' => $branchScope->currentBranchId(),
    ]);
})->name('reports');
Route::get('/reports/export', [ExportController::class, 'revenueReport'])->name('reports.export');

Route::get('/inventory', function (ManagerBranchScopeService $branchScope) {
    return redirect()->route('manager.branches.inventory', [
        'branchId' => $branchScope->currentBranchId(),
    ]);
})->name('inventory');
Route::get('/inventory/export', [ExportController::class, 'inventory'])->name('inventory.export');

Route::prefix('/branches/{branchId}')
    ->name('branches.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/services', [ServiceController::class, 'index'])->name('service');
        Route::get('/reports', [ReportController::class, 'index'])->name('reports');
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory');
    });

Route::get('/promotions', [PromotionController::class, 'index'])->name('promotions');
Route::get('/employees', [EmployeeController::class, 'index'])->name('employees');
Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
Route::patch('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
Route::post('/employees/{employee}/resign', [EmployeeController::class, 'resign'])->name('employees.resign');
