<?php

use Illuminate\Support\Facades\Route;

Route::middleware('role:ceo')
    ->prefix('ceo')
    ->name('ceo.')
    ->group(function () {
        require __DIR__ . '/dashboard.php';
        require __DIR__ . '/branch.php';
        require __DIR__ . '/finance.php';
        require __DIR__ . '/service.php';
        require __DIR__ . '/vendor.php';
    });