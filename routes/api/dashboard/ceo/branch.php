<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Ceo\BranchController;

Route::prefix('branches')
    ->name('branches.')
    ->controller(BranchController::class)
    ->group(function () {
        Route::get('/active-count', 'activeCount')->name('active-count');
        Route::get('/highest-revenue', 'highestRevenue')->name('highest-revenue');
        Route::get('/lowest-occupancy', 'lowestOccupancy')->name('lowest-occupancy');
        Route::get('/', 'index')->name('index');
    });