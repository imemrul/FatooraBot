<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CreditNote extends Model
{
    use BelongsToTenant, Auditable;

    protected $fillable = [
        'company_id', 'invoice_id', 'client_id', 'created_by',
        'credit_note_number', 'issue_date', 'subtotal', 'vat_amount',
        'total', 'currency', 'status', 'reason',
    ];

    protected function casts(): array
    {
        return ['issue_date' => 'date', 'subtotal' => 'decimal:2', 'vat_amount' => 'decimal:2', 'total' => 'decimal:2'];
    }

    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function items(): HasMany { return $this->hasMany(CreditNoteItem::class); }
    public function attachments(): MorphMany { return $this->morphMany(Attachment::class, 'attachable'); }

    public function recalculate(): void
    {
        $this->subtotal = $this->items()->sum('line_total');
        $this->vat_amount = $this->items()->sum('vat_amount');
        $this->total = $this->subtotal + $this->vat_amount;
        $this->saveQuietly();
    }
}
