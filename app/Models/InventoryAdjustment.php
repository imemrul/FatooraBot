<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryAdjustment extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'company_id', 'warehouse_id', 'adjusted_by', 'reference',
        'reason', 'notes', 'status', 'applied_at',
    ];

    protected function casts(): array
    {
        return ['applied_at' => 'datetime'];
    }

    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function adjuster(): BelongsTo { return $this->belongsTo(User::class, 'adjusted_by'); }
    public function items(): HasMany { return $this->hasMany(InventoryAdjustmentItem::class); }
}
