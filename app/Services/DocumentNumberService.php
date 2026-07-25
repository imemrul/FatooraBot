<?php

namespace App\Services;

use App\Models\DocumentNumberConfig;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class DocumentNumberService
{
    public function list(): Collection
    {
        return DocumentNumberConfig::orderBy('document_type')->get();
    }

    public function getOrCreate(string $type): DocumentNumberConfig
    {
        return DocumentNumberConfig::getOrDefault(Auth::user()->company_id, $type);
    }

    public function generate(string $type): string
    {
        $config = $this->getOrCreate($type);
        return $config->generate();
    }

    public function update(DocumentNumberConfig $config, array $data): DocumentNumberConfig
    {
        $config->update($data);
        return $config->fresh();
    }

    public function seedDefaults(int $companyId): void
    {
        $types = ['invoice', 'quotation', 'sales_order', 'purchase_order', 'credit_note', 'delivery_note'];
        foreach ($types as $type) {
            DocumentNumberConfig::getOrDefault($companyId, $type);
        }
    }
}
