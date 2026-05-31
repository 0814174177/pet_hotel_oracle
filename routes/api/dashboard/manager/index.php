<?php

use Illuminate\Support\Facades\Route;

Route::prefix('manager')
    ->name('manager.')
    ->group(function () {
        require __DIR__ . '/dashboard.php';
        require __DIR__ . '/service.php';
        require __DIR__ . '/inventory.php';
        require __DIR__ . '/report.php';
    });