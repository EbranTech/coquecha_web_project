<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ShiftController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', fn (): array => ['status' => 'ok']);
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/sectors', [CatalogController::class, 'sectors']);
        Route::get('/resources', [CatalogController::class, 'resources']);
        Route::get('/shifts', [ShiftController::class, 'index']);
        Route::post('/shifts', [ShiftController::class, 'store']);
        Route::put('/shifts/{shift}', [ShiftController::class, 'update']);
        Route::patch('/shifts/{shift}/status', [ShiftController::class, 'updateStatus']);
        Route::put('/shifts/{shift}/result', [ShiftController::class, 'storeResult']);
        Route::get('/reports', [ReportController::class, 'index']);
        Route::post('/reports', [ReportController::class, 'store']);
        Route::get('/reports/{report}', [ReportController::class, 'show']);
        Route::patch('/reports/{report}', [ReportController::class, 'update']);
        Route::post('/reports/{report}/followups', [ReportController::class, 'storeFollowup']);
        Route::post('/reports/{report}/evidences', [ReportController::class, 'storeEvidence'])->middleware('throttle:20,1');
        Route::get('/reports/{report}/evidences/{evidence}/download', [ReportController::class, 'downloadEvidence']);
    });
});
