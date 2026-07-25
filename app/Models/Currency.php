<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'company_id', 'code', 'name', 'symbol', 'rate_to_base', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rate_to_base' => 'decimal:6',
            'is_active' => 'boolean',
        ];
    }

    public function convertToBase(float $amount): float
    {
        return round($amount / (float) $this->rate_to_base, 2);
    }

    public function convertFromBase(float $baseAmount): float
    {
        return round($baseAmount * (float) $this->rate_to_base, 2);
    }
}
