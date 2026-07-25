<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomFieldDefinition extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'company_id', 'entity_type', 'field_name', 'field_label',
        'field_type', 'options', 'is_required', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return ['options' => 'array', 'is_required' => 'boolean', 'is_active' => 'boolean'];
    }

    public function values(): HasMany { return $this->hasMany(CustomFieldValue::class); }
}
