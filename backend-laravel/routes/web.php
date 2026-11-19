<?php

use App\Http\Controllers\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::livewire('/', 'pages::admin.dashboard')->name('dashboard');
    Route::livewire('/sectores', 'pages::admin.sectors')->name('sectors');
    Route::livewire('/recursos', 'pages::admin.resources')->name('resources');
    Route::livewire('/turnos', 'pages::admin.shifts')->name('shifts');
});
