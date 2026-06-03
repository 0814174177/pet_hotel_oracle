<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Manager\InventoryController;
use App\Http\Controllers\Api\Manager\InventoryManagementController;

// Quản lý kho tổng (Dành riêng cho Manager)
Route::middleware('role:manager')
    ->get('/inventory', [InventoryController::class, 'index'])
    ->name('inventory');

// Quản lý vật tư tại chi nhánh (Dành cho Manager và CEO)
Route::middleware('role:manager,ceo')
    ->prefix('branches/{branchId}/inventory/materials')
    ->name('branches.inventory.materials.')
    ->controller(InventoryManagementController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/kpi', 'kpi')->name('kpi');
        Route::get('/list', 'materials')->name('list');
        Route::get('/out-of-stock', 'outOfStock')->name('out-of-stock');
        Route::get('/low-stock', 'lowStock')->name('low-stock');
        Route::get('/inventory-value-by-category', 'inventoryValueByCategory')->name('inventory-value-by-category');
        Route::get('/{materialId}', 'show')->name('show');
    });
