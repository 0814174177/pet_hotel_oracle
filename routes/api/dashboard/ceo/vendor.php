<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Ceo\VendorController;

Route::get('/vendors', [VendorController::class, 'index'])->name('vendors');