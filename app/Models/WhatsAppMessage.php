<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppMessage extends Model
{
    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'company_id', 'phone', 'direction', 'wa_message_id',
        'body', 'message_type', 'payload', 'status',
    ];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }
}
