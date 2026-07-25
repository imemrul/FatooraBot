<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportLog extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'company_id', 'imported_by', 'type', 'filename',
        'total_rows', 'success_count', 'error_count', 'errors', 'status',
    ];

    protected function casts(): array
    {
        return ['errors' => 'array'];
    }

    public function importer(): BelongsTo { return $this->belongsTo(User::class, 'imported_by'); }
}
