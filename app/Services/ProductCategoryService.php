<?php

namespace App\Services;

use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class ProductCategoryService
{
    public function list(): Collection
    {
        return ProductCategory::with('children')
            ->withCount('products')
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();
    }

    public function all(): Collection
    {
        return ProductCategory::orderBy('name')->get(['id', 'name', 'parent_id']);
    }

    public function create(array $data): ProductCategory
    {
        $data['slug'] = Str::slug($data['name']);
        return ProductCategory::create($data);
    }

    public function update(ProductCategory $cat, array $data): ProductCategory
    {
        if (isset($data['name'])) $data['slug'] = Str::slug($data['name']);
        $cat->update($data);
        return $cat->fresh();
    }

    public function delete(ProductCategory $cat): void
    {
        $cat->delete();
    }
}
