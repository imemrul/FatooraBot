<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'object' => 'customer',
            'id' => $this->id,
            'name' => $this->name,
            'contact_person' => $this->contact_person,
            'email' => $this->email,
            'phone' => $this->phone,
            'tax_registration_number' => $this->tax_registration_number,
            'credit_limit' => (float) $this->credit_limit,
            'payment_terms' => $this->payment_terms,
            'address' => $this->address,
            'city' => $this->city,
            'country' => $this->country,
            'outstanding_balance' => (float) $this->outstanding_balance,
            'overdue_amount' => (float) $this->overdue_amount,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
