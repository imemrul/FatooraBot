<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchPaymentAllocation extends Model
{
    public $timestamps = false;

    protected $fillable = ['batch_payment_id', 'invoice_id', 'invoice_payment_id', 'amount'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function batchPayment(): BelongsTo { return $this->belongsTo(BatchPayment::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function invoicePayment(): BelongsTo { return $this->belongsTo(InvoicePayment::class); }
}
