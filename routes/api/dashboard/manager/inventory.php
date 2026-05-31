<?php

\Illuminate\Support\Facades\Route::middleware('role:manager')->get(
    '/inventory',
    [\App\Http\Controllers\Api\Manager\InventoryController::class, 'index']
)->name('inventory');

\Illuminate\Support\Facades\Route::middleware('role:manager,ceo')
    ->prefix('branches/{branchId}/inventory/materials')
    ->name('branches.inventory.materials.')
    ->controller(\App\Http\Controllers\Api\Branch\BranchInventoryMaterialController::class)
    ->group(function () {
        \Illuminate\Support\Facades\Route::get('/', 'index')->name('index');
        \Illuminate\Support\Facades\Route::get('/kpi', 'kpi')->name('kpi');
        \Illuminate\Support\Facades\Route::get('/list', 'materials')->name('list');
        \Illuminate\Support\Facades\Route::get('/{materialId}', 'show')->name('show');
        \Illuminate\Support\Facades\Route::post('/', 'store')->name('store');
        \Illuminate\Support\Facades\Route::put('/{materialId}', 'update')->name('update');
        \Illuminate\Support\Facades\Route::patch('/{materialId}/stop-import', 'stopImport')->name('stop-import');
        \Illuminate\Support\Facades\Route::patch('/{materialId}/resume-import', 'resumeImport')->name('resume-import');
    });
