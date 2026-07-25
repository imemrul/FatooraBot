<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBundleItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['product_bundle_id', 'product_id', 'quantity'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:2'];
    }

    public function bundle(): BelongsTo { return $this->belongsTo(ProductBundle::class, 'product_bundle_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
