<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory, BelongsToTenant, Auditable;

    protected $fillable = [
        'company_id',
        'name',
        'contact_person',
        'email',
        'phone',
        'tax_registration_number',
        'credit_limit',
        'payment_terms',
        'credit_hold',
        'address',
        'city',
        'country',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'payment_terms' => 'integer',
            'credit_hold' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(ClientContact::class);
    }

    public function getTotalInvoicedAttribute(): float
    {
        return (float) $this->invoices()
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->sum('total');
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) $this->invoices()
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->sum('paid_amount');
    }

    public function getOutstandingBalanceAttribute(): float
    {
        return $this->total_invoiced - $this->total_paid;
    }

    public function getOverdueAmountAttribute(): float
    {
        return (float) $this->invoices()
            ->whereNotIn('status', ['draft', 'cancelled', 'paid'])
            ->where('due_date', '<', now())
            ->selectRaw('SUM(total - paid_amount) as overdue')
            ->value('overdue') ?? 0;
    }

    public function getOverdueInvoiceCountAttribute(): int
    {
        return $this->invoices()
            ->whereNotIn('status', ['draft', 'cancelled', 'paid'])
            ->where('due_date', '<', now())
            ->count();
    }

    public function isOverCreditLimit(): bool
    {
        return $this->credit_limit > 0 && $this->outstanding_balance > $this->credit_limit;
    }
}
