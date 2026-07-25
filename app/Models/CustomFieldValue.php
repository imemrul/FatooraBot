<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CustomFieldValue extends Model
{
    protected $fillable = ['company_id', 'custom_field_definition_id', 'entity_type', 'entity_id', 'value'];

    public function definition(): BelongsTo { return $this->belongsTo(CustomFieldDefinition::class, 'custom_field_definition_id'); }
    public function entity(): MorphTo { return $this->morphTo(); }
}
