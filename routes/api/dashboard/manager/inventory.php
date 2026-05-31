<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Manager\InventoryController;
use App\Http\Controllers\Api\Branch\BranchInventoryMaterialController;

// Quản lý kho tổng (Dành riêng cho Manager)
Route::middleware('role:manager')
    ->get('/inventory', [InventoryController::class, 'index'])
    ->name('inventory');

// Quản lý vật tư tại chi nhánh (Dành cho Manager và CEO)
Route::middleware('role:manager,ceo')
    ->prefix('branches/{branchId}/inventory/materials')
    ->name('branches.inventory.materials.')
    ->controller(BranchInventoryMaterialController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/kpi', 'kpi')->name('kpi');
        Route::get('/list', 'materials')->name('list');
        Route::get('/{materialId}', 'show')->name('show');
        Route::post('/', 'store')->name('store');
        Route::put('/{materialId}', 'update')->name('update');
        Route::patch('/{materialId}/stop-import', 'stopImport')->name('stop-import');
        Route::patch('/{materialId}/resume-import', 'resumeImport')->name('resume-import');
    });