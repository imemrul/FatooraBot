<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use BelongsToTenant, Auditable;

    protected $fillable = [
        'company_id', 'supplier_id', 'warehouse_id', 'created_by',
        'po_number', 'order_date', 'expected_date',
        'subtotal', 'vat_amount', 'total', 'currency', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return ['order_date' => 'date', 'expected_date' => 'date', 'subtotal' => 'decimal:2', 'vat_amount' => 'decimal:2', 'total' => 'decimal:2'];
    }

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function items(): HasMany { return $this->hasMany(PurchaseOrderItem::class); }

    public function recalculate(): void
    {
        $this->subtotal = $this->items()->sum('line_total');
        $this->vat_amount = $this->items()->sum('vat_amount');
        $this->total = $this->subtotal + $this->vat_amount;
        $this->saveQuietly();
    }
}
