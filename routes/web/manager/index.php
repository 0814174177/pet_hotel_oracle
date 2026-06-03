<?php

use App\Http\Controllers\Web\Manager\DashboardController;
use App\Http\Controllers\Web\Manager\EmployeeController;
use App\Http\Controllers\Web\Manager\InventoryController;
use App\Http\Controllers\Web\Manager\PromotionController;
use App\Http\Controllers\Web\Manager\ReportController;
use App\Http\Controllers\Web\Manager\ServiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('index');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/services', [ServiceController::class, 'index'])->name('service');
Route::get('/reports', [ReportController::class, 'index'])->name('reports');
Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory');
Route::get('/promotions', [PromotionController::class, 'index'])->name('promotions');
Route::get('/employees', [EmployeeController::class, 'index'])->name('employees');
Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
Route::patch('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
Route::post('/employees/{employee}/resign', [EmployeeController::class, 'resign'])->name('employees.resign');
