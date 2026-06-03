<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Ceo\VendorController;

Route::get('/vendors', [VendorController::class, 'index'])->name('vendors');
Route::get('/vendors/otd', [VendorController::class, 'onTimeDeliveryPerformance'])->name('vendors.otd');
Route::get('/vendors/otd-summary', [VendorController::class, 'onTimeDeliverySummary'])->name('vendors.otd-summary');
Route::get('/vendors/payables', [VendorController::class, 'payableCashflow'])->name('vendors.payables');
Route::get('/vendors/price-variance', [VendorController::class, 'priceVarianceAlerts'])->name('vendors.price-variance');
