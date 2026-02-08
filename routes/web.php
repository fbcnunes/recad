<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ServidorController;
use App\Http\Controllers\AuthController;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [ServidorController::class, 'showSelf'])->name('dashboard');
    Route::get('/meu-cadastro', [ServidorController::class, 'showSelf'])->name('servidores.self');
    Route::put('/meu-cadastro', [ServidorController::class, 'updateSelf'])->name('servidores.self.update');
});
