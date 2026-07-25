<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BatchPayment extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'company_id', 'client_id', 'recorded_by', 'reference',
        'total_amount', 'method', 'payment_date', 'notes',
    ];

    protected function casts(): array
    {
        return ['total_amount' => 'decimal:2', 'payment_date' => 'date'];
    }

    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
    public function allocations(): HasMany { return $this->hasMany(BatchPaymentAllocation::class); }
}
