<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/refresh', [AuthController::class, 'refresh']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::middleware(['auth:sanctum', 'active.session'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

Route::middleware(['auth:sanctum', 'active.session', 'can:manage-users'])->prefix('admin')->group(function () {
    Route::get('/users', [App\Http\Controllers\AdminUserController::class, 'index']);
    Route::post('/users', [App\Http\Controllers\AdminUserController::class, 'store']);
    Route::get('/users/{id}', [App\Http\Controllers\AdminUserController::class, 'show']);
    Route::get('/roles', [App\Http\Controllers\AdminUserController::class, 'getRoles']);
    Route::get('/departments', [App\Http\Controllers\AdminUserController::class, 'getDepartments']);
});
