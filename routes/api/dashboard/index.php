<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('dashboard')
    ->name('api.dashboard.')
    ->group(function () {
        require __DIR__.'/ceo/index.php';
        require __DIR__.'/manager/index.php';
    });
