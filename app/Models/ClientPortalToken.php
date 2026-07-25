<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ClientPortalToken extends Model
{
    protected $fillable = ['company_id', 'invoice_id', 'token', 'expires_at', 'last_viewed_at'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'last_viewed_at' => 'datetime'];
    }

    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }

    public function isValid(): bool
    {
        return !$this->expires_at || $this->expires_at->isFuture();
    }

    public static function generate(int $companyId, int $invoiceId, int $daysValid = 90): static
    {
        return static::create([
            'company_id' => $companyId,
            'invoice_id' => $invoiceId,
            'token' => Str::random(64),
            'expires_at' => now()->addDays($daysValid),
        ]);
    }
}
