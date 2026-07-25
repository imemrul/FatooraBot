<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientContact extends Model
{
    use BelongsToTenant;

    protected $fillable = ['company_id', 'client_id', 'name', 'email', 'phone', 'role', 'is_primary', 'notes'];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
}
