<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'company_id', 'name', 'type', 'instructions', 'bank_name',
        'account_number', 'iban', 'swift_code', 'is_default', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['is_default' => 'boolean', 'is_active' => 'boolean'];
    }
}
