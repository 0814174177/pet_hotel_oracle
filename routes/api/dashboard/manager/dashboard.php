<?php

\Illuminate\Support\Facades\Route::middleware('role:manager')->get(
    '/overview',
    [\App\Http\Controllers\Api\Manager\DashboardController::class, 'overview']
)->name('overview');
