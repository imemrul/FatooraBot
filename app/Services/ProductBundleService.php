<?php

namespace App\Services;

use App\Models\ProductBundle;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ProductBundleService
{
    public function list(): Collection
    {
        return ProductBundle::with('items.product:id,name,unit_price,sku')->withCount('items')->get();
    }

    public function create(array $data, array $items): ProductBundle
    {
        return DB::transaction(function () use ($data, $items) {
            $bundle = ProductBundle::create($data);
            foreach ($items as $item) {
                $bundle->items()->create($item);
            }
            return $bundle->load('items.product:id,name,unit_price');
        });
    }

    public function update(ProductBundle $bundle, array $data, array $items): ProductBundle
    {
        return DB::transaction(function () use ($bundle, $data, $items) {
            $bundle->update($data);
            $bundle->items()->delete();
            foreach ($items as $item) {
                $bundle->items()->create($item);
            }
            return $bundle->fresh()->load('items.product:id,name,unit_price');
        });
    }

    public function delete(ProductBundle $bundle): void
    {
        $bundle->delete();
    }

    /**
     * Expand a bundle into invoice line items.
     */
    public function expand(ProductBundle $bundle): array
    {
        return $bundle->items->map(fn ($i) => [
            'product_id' => $i->product_id,
            'description' => $i->product->name ?? '',
            'quantity' => $i->quantity,
            'unit_price' => $i->product->unit_price ?? 0,
            'vat_rate' => $i->product->vat_rate ?? 5,
        ])->toArray();
    }
}
