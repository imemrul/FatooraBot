<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryNoteItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['delivery_note_id', 'product_id', 'description', 'quantity', 'unit'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:2'];
    }

    public function deliveryNote(): BelongsTo { return $this->belongsTo(DeliveryNote::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
