<?php

\Illuminate\Support\Facades\Route::get(
    '/finance',
    [\App\Http\Controllers\Api\Ceo\FinanceController::class, 'index']
)->name('finance');
