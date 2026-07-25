<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringInvoice extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'company_id', 'client_id', 'created_by', 'frequency', 'start_date',
        'end_date', 'next_issue_date', 'day_of_month', 'payment_terms',
        'subtotal', 'vat_amount', 'discount', 'total', 'currency',
        'notes', 'is_active', 'invoices_generated',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'next_issue_date' => 'date',
            'subtotal' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RecurringInvoiceItem::class);
    }

    public function recalculate(): void
    {
        $this->subtotal = $this->items()->sum('line_total');
        $this->vat_amount = $this->items()->sum('vat_amount');
        $this->total = $this->subtotal + $this->vat_amount - $this->discount;
        $this->saveQuietly();
    }

    public function advanceNextDate(): void
    {
        $next = match ($this->frequency) {
            'weekly' => $this->next_issue_date->addWeek(),
            'monthly' => $this->next_issue_date->addMonth(),
            'quarterly' => $this->next_issue_date->addMonths(3),
            'yearly' => $this->next_issue_date->addYear(),
        };

        if ($this->end_date && $next->gt($this->end_date)) {
            $this->update(['is_active' => false, 'next_issue_date' => $next]);
        } else {
            $this->update(['next_issue_date' => $next, 'invoices_generated' => $this->invoices_generated + 1]);
        }
    }
}
