<?php

use App\Http\Controllers\Api\SuperAdmin\DashboardController;
use App\Http\Controllers\Api\SuperAdmin\PlanController;
use App\Http\Controllers\Api\SuperAdmin\TenantController;
use App\Http\Controllers\Api\SuperAdmin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Super Admin Routes — /api/admin/*
|--------------------------------------------------------------------------
| All routes require auth:sanctum + super_admin middleware.
*/

// Platform dashboard
Route::get('/dashboard', [DashboardController::class, 'index']);

// Tenant management
Route::get('/tenants', [TenantController::class, 'index']);
Route::get('/tenants/{id}', [TenantController::class, 'show']);
Route::post('/tenants/{id}/toggle-status', [TenantController::class, 'toggleStatus']);
Route::post('/tenants/{id}/assign-plan', [TenantController::class, 'assignPlan']);
Route::post('/tenants/{id}/cancel-subscription', [TenantController::class, 'cancelSubscription']);
Route::post('/tenants/{id}/impersonate', [TenantController::class, 'impersonate']);
Route::post('/tenants/{id}/stop-impersonation', [TenantController::class, 'stopImpersonation']);

// User management
Route::get('/users', [UserController::class, 'index']);
Route::post('/users/{id}/toggle-status', [UserController::class, 'toggleStatus']);
Route::post('/users/{id}/reset-password', [UserController::class, 'resetPassword']);

// Plan management
Route::get('/plans', [PlanController::class, 'index']);
Route::post('/plans', [PlanController::class, 'store']);
Route::put('/plans/{plan}', [PlanController::class, 'update']);
Route::delete('/plans/{plan}', [PlanController::class, 'destroy']);

// Impersonation logs
Route::get('/impersonation-logs', [PlanController::class, 'impersonationLogs']);
