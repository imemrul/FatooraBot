<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use App\DTOs\StockMovementDTO;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseTransferController extends Controller
{
    public function __construct(private readonly InventoryService $service) {}

    public function store(Request $request): JsonResponse
    {
        $cid = $request->user()->company_id;
        $request->validate([
            'product_id' => "required|exists:products,id,company_id,{$cid}",
            'from_warehouse_id' => "required|exists:warehouses,id,company_id,{$cid}",
            'to_warehouse_id' => "required|exists:warehouses,id,company_id,{$cid}|different:from_warehouse_id",
            'quantity' => 'required|integer|min:1',
            'reference' => 'nullable|string|max:100',
        ]);

        $this->service->transfer(new StockMovementDTO(
            product_id: $request->product_id,
            warehouse_id: $request->from_warehouse_id,
            quantity: $request->quantity,
            type: 'transfer',
            reference: $request->reference,
            to_warehouse_id: $request->to_warehouse_id,
        ));

        return response()->json(['message' => 'Transfer completed.']);
    }

    public function history(Request $request): JsonResponse
    {
        $transfers = StockMovement::where('type', 'transfer')
            ->with(['product:id,name,sku', 'warehouse:id,name', 'creator:id,name'])
            ->latest()
            ->paginate(20);

        return response()->json($transfers);
    }
}
