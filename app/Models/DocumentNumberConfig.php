<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DocumentNumberConfig extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'company_id', 'document_type', 'prefix', 'suffix',
        'next_number', 'padding', 'separator', 'include_year',
    ];

    protected function casts(): array
    {
        return ['include_year' => 'boolean'];
    }

    public function generate(): string
    {
        return DB::transaction(function () {
            $config = static::where('id', $this->id)->lockForUpdate()->first();
            $sep = $config->separator;
            $num = str_pad($config->next_number, $config->padding, '0', STR_PAD_LEFT);

            $parts = [$config->prefix];
            if ($config->include_year) $parts[] = now()->format('Y');
            $parts[] = $num;
            if ($config->suffix) $parts[] = $config->suffix;

            $config->increment('next_number');

            return implode($sep, $parts);
        });
    }

    public static function getOrDefault(int $companyId, string $type): static
    {
        $defaults = [
            'invoice' => 'INV', 'quotation' => 'QT', 'sales_order' => 'SO',
            'purchase_order' => 'PO', 'credit_note' => 'CN', 'delivery_note' => 'DN',
        ];

        return static::firstOrCreate(
            ['company_id' => $companyId, 'document_type' => $type],
            ['prefix' => $defaults[$type] ?? strtoupper(substr($type, 0, 3)), 'next_number' => 1],
        );
    }
}
