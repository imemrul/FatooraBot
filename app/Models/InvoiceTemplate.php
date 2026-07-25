<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class InvoiceTemplate extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'company_id', 'name', 'slug', 'layout', 'primary_color', 'font',
        'show_logo', 'show_qr', 'show_payment_info', 'bilingual',
        'header_text', 'footer_text', 'is_default',
    ];

    protected function casts(): array
    {
        return ['show_logo' => 'boolean', 'show_qr' => 'boolean', 'show_payment_info' => 'boolean', 'bilingual' => 'boolean', 'is_default' => 'boolean'];
    }
}
