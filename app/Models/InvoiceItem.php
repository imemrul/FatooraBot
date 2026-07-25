<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'product_id',
        'description',
        'quantity',
        'unit_price',
        'vat_rate',
        'vat_amount',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'vat_rate' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (InvoiceItem $item) {
            $item->line_total = $item->quantity * $item->unit_price;
            $item->vat_amount = $item->line_total * ($item->vat_rate / 100);
        });

        static::updating(function (InvoiceItem $item) {
            $item->line_total = $item->quantity * $item->unit_price;
            $item->vat_amount = $item->line_total * ($item->vat_rate / 100);
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
