<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'object' => 'invoice',
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'customer_id' => $this->client_id,
            'issue_date' => $this->issue_date->toDateString(),
            'due_date' => $this->due_date->toDateString(),
            'subtotal' => (float) $this->subtotal,
            'vat_amount' => (float) $this->vat_amount,
            'discount' => (float) $this->discount,
            'total' => (float) $this->total,
            'paid_amount' => (float) $this->paid_amount,
            'balance_due' => (float) $this->balance_due,
            'currency' => $this->currency,
            'status' => $this->status,
            'is_overdue' => $this->isOverdue(),
            'notes' => $this->notes,
            'customer' => new CustomerResource($this->whenLoaded('client')),
            'line_items' => InvoiceLineItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
