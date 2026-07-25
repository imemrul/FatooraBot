<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryAdjustmentItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'inventory_adjustment_id', 'product_id',
        'system_quantity', 'actual_quantity', 'difference',
    ];

    public function adjustment(): BelongsTo { return $this->belongsTo(InventoryAdjustment::class, 'inventory_adjustment_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
