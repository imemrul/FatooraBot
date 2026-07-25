<?php

namespace App\Services;

use App\DTOs\StockMovementDTO;
use App\Models\InventoryAdjustment;
use App\Models\InventoryLevel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryAdjustmentService
{
    public function __construct(private readonly InventoryService $inventoryService) {}

    public function list(): LengthAwarePaginator
    {
        return InventoryAdjustment::with(['warehouse:id,name', 'adjuster:id,name'])
            ->withCount('items')
            ->latest()
            ->paginate(20);
    }

    public function show(InventoryAdjustment $adj): InventoryAdjustment
    {
        return $adj->load(['warehouse', 'adjuster:id,name', 'items.product:id,name,sku']);
    }

    public function create(array $data, array $items): InventoryAdjustment
    {
        return DB::transaction(function () use ($data, $items) {
            $data['adjusted_by'] = Auth::id();
            $adj = InventoryAdjustment::create($data);

            foreach ($items as $item) {
                $systemQty = InventoryLevel::where('product_id', $item['product_id'])
                    ->where('warehouse_id', $data['warehouse_id'])
                    ->value('quantity') ?? 0;

                $adj->items()->create([
                    'product_id' => $item['product_id'],
                    'system_quantity' => $systemQty,
                    'actual_quantity' => $item['actual_quantity'],
                    'difference' => $item['actual_quantity'] - $systemQty,
                ]);
            }

            return $adj->load(['warehouse:id,name', 'items.product:id,name,sku']);
        });
    }

    public function apply(InventoryAdjustment $adj): InventoryAdjustment
    {
        return DB::transaction(function () use ($adj) {
            foreach ($adj->items as $item) {
                if ($item->difference === 0) continue;

                $type = $item->difference > 0 ? 'stock_in' : 'stock_out';
                $qty = abs($item->difference);

                $this->inventoryService->{$type === 'stock_in' ? 'stockIn' : 'stockOut'}(
                    new StockMovementDTO(
                        product_id: $item->product_id,
                        warehouse_id: $adj->warehouse_id,
                        quantity: $qty,
                        type: $type,
                        reference: "Adjustment: {$adj->reason}",
                    )
                );
            }

            $adj->update(['status' => 'applied', 'applied_at' => now()]);
            return $adj->fresh();
        });
    }

    public function delete(InventoryAdjustment $adj): void
    {
        $adj->delete();
    }
}
