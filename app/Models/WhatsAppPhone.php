<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppPhone extends Model
{
    protected $table = 'whatsapp_phones';

    protected $fillable = ['company_id', 'user_id', 'phone', 'is_active', 'verified_at'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'verified_at' => 'datetime'];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public static function findByPhone(string $phone): ?static
    {
        return static::where('phone', static::normalize($phone))->where('is_active', true)->first();
    }

    public static function normalize(string $phone): string
    {
        return preg_replace('/[^0-9]/', '', $phone);
    }
}
