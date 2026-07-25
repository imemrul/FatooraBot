<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'price_monthly', 'price_yearly', 'currency',
        'max_users', 'max_invoices_per_month', 'max_products', 'max_warehouses', 'max_api_tokens',
        'feature_whatsapp_parser', 'feature_api_access', 'feature_webhooks',
        'feature_audit_log', 'feature_pdf_invoices', 'feature_payment_reminders',
        'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'is_active' => 'boolean',
            'feature_whatsapp_parser' => 'boolean',
            'feature_api_access' => 'boolean',
            'feature_webhooks' => 'boolean',
            'feature_audit_log' => 'boolean',
            'feature_pdf_invoices' => 'boolean',
            'feature_payment_reminders' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function hasFeature(string $feature): bool
    {
        $key = "feature_{$feature}";

        return $this->getAttribute($key) === true;
    }

    public function getLimit(string $resource): int
    {
        $key = "max_{$resource}";

        return (int) ($this->getAttribute($key) ?? 0);
    }
}
