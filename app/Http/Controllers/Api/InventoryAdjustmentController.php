<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryAdjustment;
use App\Services\InventoryAdjustmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryAdjustmentController extends Controller
{
    public function __construct(private readonly InventoryAdjustmentService $service) {}

    public function index(): JsonResponse
    {
        return response()->json($this->service->list());
    }

    public function show(InventoryAdjustment $inventoryAdjustment): JsonResponse
    {
        return response()->json(['adjustment' => $this->service->show($inventoryAdjustment)]);
    }

    public function store(Request $request): JsonResponse
    {
        $cid = $request->user()->company_id;
        $request->validate([
            'warehouse_id' => "required|exists:warehouses,id,company_id,{$cid}",
            'reason' => 'required|in:stock_count,damage,theft,correction,other',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => "required|exists:products,id,company_id,{$cid}",
            'items.*.actual_quantity' => 'required|integer|min:0',
        ]);
        $adj = $this->service->create($request->only('warehouse_id', 'reason', 'notes'), $request->items);
        return response()->json(['adjustment' => $adj, 'message' => 'Adjustment created.'], 201);
    }

    public function apply(InventoryAdjustment $inventoryAdjustment): JsonResponse
    {
        $adj = $this->service->apply($inventoryAdjustment);
        return response()->json(['adjustment' => $adj, 'message' => 'Adjustment applied. Stock updated.']);
    }

    public function destroy(InventoryAdjustment $inventoryAdjustment): JsonResponse
    {
        $this->service->delete($inventoryAdjustment);
        return response()->json(['message' => 'Deleted.']);
    }
}
