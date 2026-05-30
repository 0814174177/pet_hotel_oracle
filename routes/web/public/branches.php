<?php

use App\Http\Controllers\Web\Default\BranchController;
use Illuminate\Support\Facades\Route;

// NHÓM CHI NHÁNH (BRANCHES)
Route::prefix('branches')->name('branches.')->group(function () {
    
    // URL: /branches | Name: branches.index
    Route::get('/', [BranchController::class, 'index'])->name('index');

    // URL: /branches/filter | Name: branches.filter
    Route::get('/filter', [BranchController::class, 'filter'])->name('filter');
    
    // URL: /branches/{branchId} | Name: branches.show
    Route::get('/{branchId}', [BranchController::class, 'show'])->name('show');
    
});
