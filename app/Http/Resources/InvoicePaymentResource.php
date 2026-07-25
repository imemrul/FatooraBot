<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoicePaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'method' => $this->method,
            'reference' => $this->reference,
            'payment_date' => $this->payment_date->toDateString(),
            'notes' => $this->notes,
            'recorded_by' => new UserResource($this->whenLoaded('recorder')),
            'created_at' => $this->created_at,
        ];
    }
}
