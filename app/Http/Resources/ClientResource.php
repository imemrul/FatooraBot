<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'contact_person' => $this->contact_person,
            'email' => $this->email,
            'phone' => $this->phone,
            'tax_registration_number' => $this->tax_registration_number,
            'credit_limit' => $this->credit_limit,
            'payment_terms' => $this->payment_terms,
            'address' => $this->address,
            'city' => $this->city,
            'country' => $this->country,
            'notes' => $this->notes,
            'total_invoiced' => $this->total_invoiced,
            'total_paid' => $this->total_paid,
            'outstanding_balance' => $this->outstanding_balance,
            'overdue_amount' => $this->overdue_amount,
            'overdue_invoice_count' => $this->overdue_invoice_count,
            'over_credit_limit' => $this->isOverCreditLimit(),
            'created_at' => $this->created_at,
        ];
    }
}
