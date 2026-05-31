<?php

\Illuminate\Support\Facades\Route::get(
    '/vendors',
    [\App\Http\Controllers\Api\Ceo\VendorController::class, 'index']
)->name('vendors');
