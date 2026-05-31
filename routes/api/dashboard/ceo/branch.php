<?php

\Illuminate\Support\Facades\Route::prefix('branches')
    ->name('branches.')
    ->controller(\App\Http\Controllers\Api\Ceo\BranchController::class)
    ->group(function () {
        \Illuminate\Support\Facades\Route::get('/active-count', 'activeCount')->name('active-count');
        \Illuminate\Support\Facades\Route::get('/highest-revenue', 'highestRevenue')->name('highest-revenue');
        \Illuminate\Support\Facades\Route::get('/lowest-occupancy', 'lowestOccupancy')->name('lowest-occupancy');
        \Illuminate\Support\Facades\Route::get('/', 'index')->name('index');
    });
