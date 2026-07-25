<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\StockMovementDTO;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\InventoryResource;
use App\Http\Resources\V1\ProductResource;
use App\Models\InventoryLevel;
use App\Services\InventoryService;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryService $service,
        private readonly WebhookService $webhooks,
    ) {}

    public function levels(Request $request): JsonResponse
    {
        $levels = InventoryLevel::with(['product', 'warehouse'])
            ->when($request->has('product_id'), fn ($q) => $q->where('product_id', $request->input('product_id')))
            ->when($request->has('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->input('warehouse_id')))
            ->where('quantity', '>', 0)
            ->paginate($request->integer('per_page', 50));

        return InventoryResource::collection($levels)->response();
    }

    public function stockIn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $movement = $this->service->stockIn(new StockMovementDTO(
            product_id: $validated['product_id'],
            warehouse_id: $validated['warehouse_id'],
            type: 'stock_in',
            quantity: $validated['quantity'],
            reference: $validated['reference'] ?? null,
            notes: $validated['notes'] ?? null,
        ));

        $this->webhooks->dispatch($request->user()->company_id, 'inventory.stock_in', [
            'product_id' => $validated['product_id'],
            'warehouse_id' => $validated['warehouse_id'],
            'quantity' => $validated['quantity'],
            'movement_id' => $movement->id,
        ]);

        return response()->json(['object' => 'stock_movement', 'id' => $movement->id, 'type' => 'stock_in', 'quantity' => $movement->quantity], 201);
    }

    public function stockOut(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $movement = $this->service->stockOut(new StockMovementDTO(
            product_id: $validated['product_id'],
            warehouse_id: $validated['warehouse_id'],
            type: 'stock_out',
            quantity: $validated['quantity'],
            reference: $validated['reference'] ?? null,
            notes: $validated['notes'] ?? null,
        ));

        $this->webhooks->dispatch($request->user()->company_id, 'inventory.stock_out', [
            'product_id' => $validated['product_id'],
            'warehouse_id' => $validated['warehouse_id'],
            'quantity' => $validated['quantity'],
            'movement_id' => $movement->id,
        ]);

        return response()->json(['object' => 'stock_movement', 'id' => $movement->id, 'type' => 'stock_out', 'quantity' => $movement->quantity], 201);
    }

    public function alerts(): JsonResponse
    {
        $alerts = $this->service->getAlerts();

        return response()->json([
            'low_stock' => ProductResource::collection($alerts['low_stock']),
            'out_of_stock' => ProductResource::collection($alerts['out_of_stock']),
        ]);
    }
}
