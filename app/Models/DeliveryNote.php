<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryNote extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'company_id', 'sales_order_id', 'client_id', 'created_by',
        'delivery_number', 'delivery_date', 'driver_name', 'vehicle_number',
        'delivery_address', 'status', 'notes', 'delivered_at',
    ];

    protected function casts(): array
    {
        return ['delivery_date' => 'date', 'delivered_at' => 'datetime'];
    }

    public function salesOrder(): BelongsTo { return $this->belongsTo(SalesOrder::class); }
    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function items(): HasMany { return $this->hasMany(DeliveryNoteItem::class); }
}
