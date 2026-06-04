<?php

use App\Http\Controllers\Web\Ceo\BranchController;
use App\Http\Controllers\Web\Ceo\DashboardController;
use App\Http\Controllers\Web\Ceo\EmployeeController;
use App\Http\Controllers\Web\Ceo\FinanceController;
use App\Http\Controllers\Web\Ceo\PromotionController;
use App\Http\Controllers\Web\Ceo\ServiceController;
use App\Http\Controllers\Web\Ceo\VendorController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('index');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/branches', [BranchController::class, 'index'])->name('branches');
Route::get('/services', [ServiceController::class, 'index'])->name('service');
Route::get('/vendors', [VendorController::class, 'index'])->name('vendors');
Route::get('/finance', [FinanceController::class, 'index'])->name('finance');
Route::get('/promotions', [PromotionController::class, 'index'])->name('promotions');
Route::post('/promotions', [PromotionController::class, 'store'])->name('promotions.store');
Route::post('/promotions/{coupon}/end', [PromotionController::class, 'end'])->name('promotions.end');
Route::get('/employees', [EmployeeController::class, 'index'])->name('employees');
Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
Route::patch('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
Route::post('/employees/{employee}/resign', [EmployeeController::class, 'resign'])->name('employees.resign');
