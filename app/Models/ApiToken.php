<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ApiToken extends Model
{
    protected $fillable = [
        'company_id',
        'created_by',
        'name',
        'token',
        'scopes',
        'rate_limit',
        'last_used_at',
        'expires_at',
        'is_active',
    ];

    protected $hidden = ['token'];

    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'rate_limit' => 'integer',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function hasScope(string $scope): bool
    {
        $scopes = $this->scopes ?? ['*'];

        return in_array('*', $scopes) || in_array($scope, $scopes);
    }

    public function isValid(): bool
    {
        if (!$this->is_active) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;

        return true;
    }

    public function markUsed(): void
    {
        $cacheKey = "api_token_used:{$this->id}";
        if (!Cache::has($cacheKey)) {
            static::withoutEvents(function () {
                $this->updateQuietly(['last_used_at' => now()]);
            });
            Cache::put($cacheKey, true, 60);
        }
    }

    public static function findByPlainToken(string $plainToken): ?self
    {
        $hash = hash('sha256', $plainToken);

        return static::where('token', $hash)->first();
    }

    public static function generateToken(): array
    {
        $plain = Str::random(64);
        $hash = hash('sha256', $plain);

        return ['plain' => $plain, 'hash' => $hash];
    }
}
