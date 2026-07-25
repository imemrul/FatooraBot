<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppConversation extends Model
{
    protected $table = 'whatsapp_conversations';

    protected $fillable = ['phone', 'company_id', 'user_id', 'state', 'intent', 'context', 'expires_at'];

    protected function casts(): array
    {
        return ['context' => 'array', 'expires_at' => 'datetime'];
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function reset(): void
    {
        $this->update(['state' => 'idle', 'intent' => null, 'context' => null, 'expires_at' => null]);
    }

    public function setState(string $state, string $intent, array $context = []): void
    {
        $this->update([
            'state' => $state,
            'intent' => $intent,
            'context' => $context,
            'expires_at' => now()->addMinutes(15),
        ]);
    }

    public static function getOrCreate(string $phone, ?int $companyId = null, ?int $userId = null): static
    {
        return static::firstOrCreate(
            ['phone' => $phone],
            ['company_id' => $companyId, 'user_id' => $userId, 'state' => 'idle'],
        );
    }
}
