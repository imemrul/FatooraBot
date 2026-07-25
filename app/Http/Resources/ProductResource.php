<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'name' => $this->name,
            'description' => $this->description,
            'unit_price' => $this->unit_price,
            'cost_price' => $this->cost_price,
            'vat_rate' => $this->vat_rate,
            'unit' => $this->unit,
            'low_stock_threshold' => $this->low_stock_threshold,
            'total_stock' => $this->total_stock,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
        ];
    }
}
