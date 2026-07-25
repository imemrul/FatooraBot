<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'order_date' => $this->order_date->toDateString(),
            'delivery_date' => $this->delivery_date?->toDateString(),
            'subtotal' => $this->subtotal,
            'vat_amount' => $this->vat_amount,
            'total' => $this->total,
            'currency' => $this->currency,
            'status' => $this->status,
            'notes' => $this->notes,
            'invoice_id' => $this->invoice_id,
            'client' => new ClientResource($this->whenLoaded('client')),
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
            'created_by' => new UserResource($this->whenLoaded('creator')),
            'invoice' => new InvoiceResource($this->whenLoaded('invoice')),
            'items' => SalesOrderItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
        ];
    }
}
