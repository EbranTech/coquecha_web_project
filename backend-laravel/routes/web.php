<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ResourceController;
use App\Http\Controllers\Admin\SectorController;
use App\Http\Controllers\Admin\ShiftController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/sectores', [SectorController::class, 'index'])->name('sectors');
    Route::post('/sectores', [SectorController::class, 'store'])->name('sectors.store');
    Route::get('/sectores/{sector}/editar', [SectorController::class, 'edit'])->name('sectors.edit');
    Route::put('/sectores/{sector}', [SectorController::class, 'update'])->name('sectors.update');
    Route::delete('/sectores/{sector}', [SectorController::class, 'destroy'])->name('sectors.destroy');
    Route::get('/recursos', [ResourceController::class, 'index'])->name('resources');
    Route::post('/recursos', [ResourceController::class, 'store'])->name('resources.store');
    Route::get('/recursos/{resource}/editar', [ResourceController::class, 'edit'])->name('resources.edit');
    Route::put('/recursos/{resource}', [ResourceController::class, 'update'])->name('resources.update');
    Route::delete('/recursos/{resource}', [ResourceController::class, 'destroy'])->name('resources.destroy');
    Route::get('/usuarios', [UserController::class, 'index'])->name('users');
    Route::post('/usuarios', [UserController::class, 'store'])->name('users.store');
    Route::get('/usuarios/{user}/editar', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/usuarios/{user}', [UserController::class, 'update'])->name('users.update');
    Route::get('/turnos', [ShiftController::class, 'index'])->name('shifts');
    Route::post('/turnos', [ShiftController::class, 'store'])->name('shifts.store');
    Route::patch('/turnos/{shift}/estado', [ShiftController::class, 'updateStatus'])->name('shifts.status');
});
