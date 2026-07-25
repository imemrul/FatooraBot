<?php

namespace App\Services;

use App\DTOs\StockMovementDTO;
use App\Models\InventoryLevel;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function stockIn(StockMovementDTO $dto): StockMovement
    {
        return DB::transaction(function () use ($dto) {
            $this->adjustLevel($dto->product_id, $dto->warehouse_id, $dto->quantity);

            return StockMovement::create([
                'product_id' => $dto->product_id,
                'warehouse_id' => $dto->warehouse_id,
                'created_by' => Auth::id(),
                'type' => 'stock_in',
                'quantity' => $dto->quantity,
                'reference' => $dto->reference,
                'notes' => $dto->notes,
            ]);
        });
    }

    public function stockOut(StockMovementDTO $dto): StockMovement
    {
        return DB::transaction(function () use ($dto) {
            $this->ensureSufficientStock($dto->product_id, $dto->warehouse_id, $dto->quantity);
            $this->adjustLevel($dto->product_id, $dto->warehouse_id, -$dto->quantity);

            return StockMovement::create([
                'product_id' => $dto->product_id,
                'warehouse_id' => $dto->warehouse_id,
                'created_by' => Auth::id(),
                'type' => 'stock_out',
                'quantity' => $dto->quantity,
                'reference' => $dto->reference,
                'notes' => $dto->notes,
            ]);
        });
    }

    public function transfer(StockMovementDTO $dto): StockMovement
    {
        return DB::transaction(function () use ($dto) {
            $this->ensureSufficientStock($dto->product_id, $dto->warehouse_id, $dto->quantity);
            $this->adjustLevel($dto->product_id, $dto->warehouse_id, -$dto->quantity);
            $this->adjustLevel($dto->product_id, $dto->to_warehouse_id, $dto->quantity);

            return StockMovement::create([
                'product_id' => $dto->product_id,
                'warehouse_id' => $dto->warehouse_id,
                'to_warehouse_id' => $dto->to_warehouse_id,
                'created_by' => Auth::id(),
                'type' => 'transfer',
                'quantity' => $dto->quantity,
                'reference' => $dto->reference,
                'notes' => $dto->notes,
            ]);
        });
    }

    public function getMovements(int $perPage = 20): LengthAwarePaginator
    {
        return StockMovement::with(['product', 'warehouse', 'toWarehouse', 'creator'])
            ->latest()
            ->paginate($perPage);
    }

    public function getLevels(int $perPage = 20): LengthAwarePaginator
    {
        return InventoryLevel::with(['product', 'warehouse'])
            ->where('quantity', '>', 0)
            ->paginate($perPage);
    }

    public function getLowStockProducts(): \Illuminate\Support\Collection
    {
        // Database-level aggregation instead of loading all products into memory
        return DB::table('products')
            ->join('inventory_levels', 'products.id', '=', 'inventory_levels.product_id')
            ->where('products.is_active', true)
            ->where('products.company_id', Auth::user()?->company_id)
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.low_stock_threshold')
            ->havingRaw('SUM(inventory_levels.quantity) > 0')
            ->havingRaw('SUM(inventory_levels.quantity) <= products.low_stock_threshold')
            ->select([
                'products.id',
                'products.name',
                'products.sku',
                'products.low_stock_threshold',
                DB::raw('SUM(inventory_levels.quantity) as total_stock'),
            ])
            ->get();
    }

    public function getOutOfStockProducts(): \Illuminate\Database\Eloquent\Collection
    {
        $withStock = InventoryLevel::where('quantity', '>', 0)
            ->pluck('product_id')
            ->unique();

        return Product::where('is_active', true)
            ->whereNotIn('id', $withStock)
            ->select('id', 'name', 'sku', 'low_stock_threshold')
            ->get();
    }

    public function getAlerts(): array
    {
        return [
            'low_stock' => $this->getLowStockProducts()->values(),
            'out_of_stock' => $this->getOutOfStockProducts()->values(),
        ];
    }

    private function adjustLevel(int $productId, int $warehouseId, int $delta): void
    {
        $level = InventoryLevel::firstOrCreate(
            ['product_id' => $productId, 'warehouse_id' => $warehouseId],
            ['company_id' => Auth::user()->company_id, 'quantity' => 0],
        );

        $level->increment('quantity', $delta);
    }

    private function ensureSufficientStock(int $productId, int $warehouseId, int $needed): void
    {
        // Use lockForUpdate to prevent race condition
        $current = InventoryLevel::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->lockForUpdate()
            ->value('quantity') ?? 0;

        if ($current < $needed) {
            throw ValidationException::withMessages([
                'quantity' => ["Insufficient stock. Available: {$current}, requested: {$needed}."],
            ]);
        }
    }
}
