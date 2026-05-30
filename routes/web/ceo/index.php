<?php

use App\Http\Controllers\Web\Ceo\BranchController;
use App\Http\Controllers\Web\Ceo\DashboardController;
use App\Http\Controllers\Web\Ceo\FinanceController;
use App\Http\Controllers\Web\Ceo\ServiceController;
use App\Http\Controllers\Web\Ceo\VendorController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('index');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/branches', [BranchController::class, 'index'])->name('branches');
Route::get('/services', [ServiceController::class, 'index'])->name('service');
Route::get('/vendors', [VendorController::class, 'index'])->name('vendors');
Route::get('/finance', [FinanceController::class, 'index'])->name('finance');
