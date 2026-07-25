<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory, BelongsToTenant, Auditable;

    protected $fillable = [
        'company_id',
        'product_category_id',
        'sku',
        'barcode',
        'name',
        'description',
        'unit_price',
        'cost_price',
        'vat_rate',
        'unit',
        'low_stock_threshold',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'vat_rate' => 'decimal:2',
            'low_stock_threshold' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function inventoryLevels(): HasMany
    {
        return $this->hasMany(InventoryLevel::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function getTotalStockAttribute(): int
    {
        return $this->inventoryLevels()->sum('quantity');
    }

    public function isLowStock(): bool
    {
        return $this->total_stock > 0 && $this->total_stock <= $this->low_stock_threshold;
    }

    public function isOutOfStock(): bool
    {
        return $this->total_stock <= 0;
    }
}
