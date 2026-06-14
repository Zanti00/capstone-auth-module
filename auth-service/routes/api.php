<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/encryption-key', [AuthController::class, 'getEncryptionKey']);
Route::post('/login', [AuthController::class, 'login'])->middleware(['throttle:auth', 'decrypt.rsa:password']);
Route::post('/refresh', [AuthController::class, 'refresh']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:auth');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware(['throttle:auth', 'decrypt.rsa:password,password_confirmation']);
Route::get('/verify-email', [AuthController::class, 'verifyEmail']);

Route::get('/internal/audit-logs', [\App\Http\Controllers\InternalAuditLogController::class, 'index']);

Route::middleware(['auth:api', 'active.session'])->group(function () {
    // Routes that must be accessible even if password change is required
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/me/password', [AuthController::class, 'changePassword'])->middleware('decrypt.rsa:current_password,new_password,new_password_confirmation');

    // Routes that require password change
    Route::middleware('require.password.change')->group(function () {
        Route::post('/send-verification', [AuthController::class, 'sendVerification']);
        Route::post('/verify-password', [AuthController::class, 'verifyPassword']);
        Route::get('/user', function (Request $request) {
            return $request->user();
        });
        Route::get('/me/permissions', [AuthController::class, 'permissions']);
        Route::put('/me/profile', [AuthController::class, 'updateProfile']);
    });
});

Route::middleware(['auth:api', 'active.session', 'require.password.change', 'can:manage-users'])->prefix('admin')->group(function () {
    Route::get('/users', [App\Http\Controllers\AdminUserController::class, 'index']);
    Route::post('/users', [App\Http\Controllers\AdminUserController::class, 'store']);
    Route::get('/users/{id}', [App\Http\Controllers\AdminUserController::class, 'show']);
    Route::patch('/users/{id}/status', [App\Http\Controllers\AdminUserController::class, 'toggleStatus']);
    Route::get('/department-options', [App\Http\Controllers\AdminUserController::class, 'getDepartments']);
    Route::get('/role-options', [App\Http\Controllers\AdminUserController::class, 'getRoles']);
});

Route::middleware(['auth:api', 'active.session', 'require.password.change', 'can:manage-roles'])->prefix('admin')->group(function () {
    Route::apiResource('roles', App\Http\Controllers\RoleController::class);
    Route::get('roles/{id}/users', [App\Http\Controllers\RoleController::class, 'users']);
    Route::patch('users/{id}/role', [App\Http\Controllers\RoleController::class, 'assignRole']);
    
    // Permission Management
    Route::apiResource('permissions', App\Http\Controllers\PermissionController::class);
    Route::get('permissions/{id}/roles', [App\Http\Controllers\PermissionController::class, 'getRoles']);
    Route::post('permissions/{id}/roles', [App\Http\Controllers\PermissionController::class, 'syncRoles']);
    Route::get('roles/{id}/permissions', [App\Http\Controllers\RoleController::class, 'permissions']);
    Route::post('roles/{id}/permissions', [App\Http\Controllers\RoleController::class, 'syncPermissions']);
});

Route::middleware(['auth:api', 'active.session', 'require.password.change', 'can:manage-departments'])->prefix('admin')->group(function () {
    Route::apiResource('departments', App\Http\Controllers\DepartmentController::class);
    Route::get('departments/{id}/users', [App\Http\Controllers\DepartmentController::class, 'users']);
    Route::patch('users/{id}/department', [App\Http\Controllers\DepartmentController::class, 'assignDepartment']);
});

Route::middleware(['auth:api', 'active.session', 'require.password.change'])->group(function () {
    Route::get('users/{id}/permissions', [App\Http\Controllers\AdminUserController::class, 'getUserPermissions']);
});
