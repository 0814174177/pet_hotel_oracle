<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Manager\DashboardController;

Route::middleware('role:manager')->get('/overview', [DashboardController::class, 'overview'])->name('overview');