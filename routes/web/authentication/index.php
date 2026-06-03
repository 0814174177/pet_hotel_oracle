<?php

use App\Http\Controllers\Web\Authentication\ForgotPasswordController;
use App\Http\Controllers\Web\Authentication\LoginController;
use App\Http\Controllers\Web\Authentication\RegisterController;
use App\Http\Controllers\Web\Authentication\ResetPasswordController;
use Illuminate\Support\Facades\Route;

Route::prefix('authentication')
    ->name('authentication.')
    ->middleware('guest')
    ->group(function () {
        Route::get('/login', [LoginController::class, 'show'])->name('login');
        Route::post('/login', [LoginController::class, 'store'])->name('login.store');

        Route::get('/register', [RegisterController::class, 'show'])->name('register');
        Route::post('/register', [RegisterController::class, 'store'])->name('register.store');

        Route::get('/forgot-password', [ForgotPasswordController::class, 'show'])->name('forgot-password');
        Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('forgot-password.store');

        Route::get('/reset-password/{token}', [ResetPasswordController::class, 'show'])->name('reset-password');
        Route::post('/reset-password.store', [ResetPasswordController::class, 'store'])->name('reset-password.store');
    });

Route::post('/authentication/logout', [LoginController::class, 'destroy'])
    ->middleware(['auth', 'no.cache'])
    ->name('authentication.logout');
