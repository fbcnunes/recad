<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ServidorController;
use App\Http\Controllers\AuthController;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/servidores', [ServidorController::class, 'index'])->name('servidores.index');
    Route::get('/servidores/novo', [ServidorController::class, 'create'])->name('servidores.create');
    Route::post('/servidores', [ServidorController::class, 'store'])->name('servidores.store');
});
