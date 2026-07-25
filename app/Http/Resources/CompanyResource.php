<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'trade_license_number' => $this->trade_license_number,
            'tax_registration_number' => $this->tax_registration_number,
            'address' => $this->address,
            'city' => $this->city,
            'country' => $this->country,
            'currency' => $this->currency,
            'logo_path' => $this->logo_path,
            'logo_url' => $this->logo_url,
            'onboarded' => $this->isOnboarded(),
            'onboarded_at' => $this->onboarded_at,
        ];
    }
}
