<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quotation extends Model
{
    use BelongsToTenant, Auditable;

    protected $fillable = [
        'company_id', 'client_id', 'created_by', 'invoice_id',
        'quotation_number', 'issue_date', 'valid_until',
        'subtotal', 'vat_amount', 'discount', 'total',
        'currency', 'status', 'notes', 'terms',
    ];

    protected function casts(): array
    {
        return ['issue_date' => 'date', 'valid_until' => 'date', 'subtotal' => 'decimal:2', 'vat_amount' => 'decimal:2', 'discount' => 'decimal:2', 'total' => 'decimal:2'];
    }

    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function items(): HasMany { return $this->hasMany(QuotationItem::class); }

    public function recalculate(): void
    {
        $this->subtotal = $this->items()->sum('line_total');
        $this->vat_amount = $this->items()->sum('vat_amount');
        $this->total = $this->subtotal + $this->vat_amount - $this->discount;
        $this->saveQuietly();
    }

    public function isExpired(): bool
    {
        return $this->valid_until->isPast() && !in_array($this->status, ['approved', 'converted']);
    }
}
