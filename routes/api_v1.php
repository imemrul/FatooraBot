<?php

use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\TokenController;
use App\Http\Controllers\Api\V1\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| FatooraBot External API v1
|--------------------------------------------------------------------------
|
| Authentication: Bearer token from /api/v1/tokens endpoint
| Rate Limit: per-token configurable (default 60/min)
| Base URL: /api/v1
|
| Token management uses Sanctum auth (SPA/internal).
| All other v1 routes use API token auth.
|
*/

// ── Token management (Sanctum auth — internal users create tokens) ──
Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('/tokens', [TokenController::class, 'index']);
    Route::post('/tokens', [TokenController::class, 'store']);
    Route::delete('/tokens/{token}', [TokenController::class, 'destroy']);
});

// ── External API (API token auth) ──
Route::middleware('api.token')->group(function () {

    // Products
    Route::middleware('api.token:products.read')->group(function () {
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/{product}', [ProductController::class, 'show']);
    });
    Route::middleware('api.token:products.write')->group(function () {
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{product}', [ProductController::class, 'update']);
    });

    // Customers
    Route::middleware('api.token:customers.read')->group(function () {
        Route::get('/customers', [CustomerController::class, 'index']);
        Route::get('/customers/{customer}', [CustomerController::class, 'show']);
    });
    Route::middleware('api.token:customers.write')->group(function () {
        Route::post('/customers', [CustomerController::class, 'store']);
        Route::put('/customers/{customer}', [CustomerController::class, 'update']);
    });

    // Invoices
    Route::middleware('api.token:invoices.read')->group(function () {
        Route::get('/invoices', [InvoiceController::class, 'index']);
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
    });
    Route::middleware('api.token:invoices.write')->group(function () {
        Route::post('/invoices', [InvoiceController::class, 'store']);
    });

    // Inventory
    Route::middleware('api.token:inventory.read')->group(function () {
        Route::get('/inventory', [InventoryController::class, 'levels']);
        Route::get('/inventory/alerts', [InventoryController::class, 'alerts']);
    });
    Route::middleware('api.token:inventory.write')->group(function () {
        Route::post('/inventory/stock-in', [InventoryController::class, 'stockIn']);
        Route::post('/inventory/stock-out', [InventoryController::class, 'stockOut']);
    });

    // Webhooks
    Route::middleware('api.token:webhooks.manage')->group(function () {
        Route::get('/webhooks', [WebhookController::class, 'index']);
        Route::post('/webhooks', [WebhookController::class, 'store']);
        Route::get('/webhooks/events', [WebhookController::class, 'events']);
        Route::get('/webhooks/{webhook}', [WebhookController::class, 'show']);
        Route::put('/webhooks/{webhook}', [WebhookController::class, 'update']);
        Route::delete('/webhooks/{webhook}', [WebhookController::class, 'destroy']);
        Route::get('/webhooks/{webhook}/logs', [WebhookController::class, 'logs']);
    });
});
