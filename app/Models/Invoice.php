<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory, BelongsToTenant, Auditable;

    protected $fillable = [
        'company_id',
        'client_id',
        'created_by',
        'invoice_number',
        'uuid',
        'issue_date',
        'due_date',
        'subtotal',
        'vat_amount',
        'total',
        'discount',
        'paid_amount',
        'currency',
        'status',
        'zatca_status',
        'zatca_qr_tlv',
        'zatca_xml',
        'zatca_hash',
        'zatca_submitted_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'discount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'zatca_submitted_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
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
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(PaymentReminder::class);
    }

    public function getBalanceDueAttribute(): float
    {
        return (float) $this->total - (float) $this->paid_amount;
    }

    public function isOverdue(): bool
    {
        return !in_array($this->status, ['draft', 'cancelled', 'paid'])
            && $this->due_date->isPast()
            && $this->balance_due > 0;
    }

    public function syncPaidAmount(): void
    {
        $totalPaid = $this->payments()->sum('amount');
        $this->paid_amount = $totalPaid;

        if ($totalPaid >= (float) $this->total && $this->status !== 'cancelled') {
            $this->status = 'paid';
        } elseif ($this->status === 'paid' && $totalPaid < (float) $this->total) {
            $this->status = 'sent';
        }

        $this->saveQuietly();
    }

    public function recalculateTotals(): void
    {
        $this->subtotal = $this->items()->sum('line_total');
        $this->vat_amount = $this->items()->sum('vat_amount');
        $this->total = $this->subtotal + $this->vat_amount - $this->discount;
        $this->saveQuietly();
    }

    public function generateQrData(): string
    {
        $company = $this->company;

        return implode("\n", [
            $company->name,
            $company->tax_registration_number ?? '',
            $this->issue_date->toDateString(),
            number_format((float) $this->total, 2, '.', ''),
            number_format((float) $this->vat_amount, 2, '.', ''),
        ]);
    }
}
