<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'trade_license_number',
        'tax_registration_number',
        'address',
        'city',
        'country',
        'currency',
        'logo_path',
        'is_active',
        'onboarded_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'onboarded_at' => 'datetime',
        ];
    }

    public function isOnboarded(): bool
    {
        return !is_null($this->onboarded_at);
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        return Storage::disk('public')->url($this->logo_path);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class)->active()->latest();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function currentPlan(): ?Plan
    {
        return $this->subscription?->plan;
    }

    public function hasFeature(string $feature): bool
    {
        $plan = $this->currentPlan();

        return $plan ? $plan->hasFeature($feature) : false;
    }
}
