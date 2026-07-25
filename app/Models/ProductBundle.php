<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductBundle extends Model
{
    use BelongsToTenant;

    protected $fillable = ['company_id', 'name', 'sku', 'description', 'bundle_price', 'is_active'];

    protected function casts(): array
    {
        return ['bundle_price' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function items(): HasMany { return $this->hasMany(ProductBundleItem::class); }

    public function getCalculatedPriceAttribute(): float
    {
        if ($this->bundle_price) return (float) $this->bundle_price;
        return (float) $this->items->sum(fn ($i) => $i->quantity * ($i->product?->unit_price ?? 0));
    }
}
