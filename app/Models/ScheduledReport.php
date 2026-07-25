<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledReport extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'company_id', 'created_by', 'report_type', 'frequency',
        'email_to', 'is_active', 'last_sent_at', 'next_send_at',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'last_sent_at' => 'datetime', 'next_send_at' => 'datetime'];
    }

    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function advanceNextSend(): void
    {
        $next = $this->frequency === 'weekly' ? now()->addWeek() : now()->addMonth();
        $this->update(['last_sent_at' => now(), 'next_send_at' => $next]);
    }
}
