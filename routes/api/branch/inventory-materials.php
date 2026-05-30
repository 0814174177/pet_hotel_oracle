<?php

use App\Http\Controllers\Api\Branch\BranchInventoryMaterialController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:manager,ceo'])
    ->prefix('branches/{branchId}/inventory/materials')
    ->name('api.branches.inventory.materials.')
    ->controller(BranchInventoryMaterialController::class)
    ->group(function () {
        // TODO: Add branch membership and inventory action authorization middleware or policies when available.
        Route::get('/', 'index')->name('index');
        Route::get('/kpi', 'kpi')->name('kpi');
        Route::get('/list', 'materials')->name('list');
        Route::get('/{materialId}', 'show')->name('show');
        Route::post('/', 'store')->name('store');
        Route::put('/{materialId}', 'update')->name('update');
        Route::patch('/{materialId}/stop-import', 'stopImport')->name('stop-import');
        Route::patch('/{materialId}/resume-import', 'resumeImport')->name('resume-import');
    });
