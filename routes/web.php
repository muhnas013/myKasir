<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PinLoginController;
use App\Http\Controllers\HomeController;
use App\Livewire\Settings\Index as SettingsIndex;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::get('/login/pin', [PinLoginController::class, 'show'])->name('login.pin');
Route::post('/login/pin', [PinLoginController::class, 'store'])->name('login.pin.store');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::post('/lock', [PinLoginController::class, 'lock'])->name('lock');

    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/pos', [HomeController::class, 'posPlaceholder'])->name('pos.placeholder');

    Route::get('/settings', SettingsIndex::class)
        ->middleware('can:settings.manage')
        ->name('settings.index');
});
