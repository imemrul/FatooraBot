<?php

namespace App\DTOs;

readonly class ClientDTO
{
    public function __construct(
        public string $name,
        public ?string $contact_person = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $tax_registration_number = null,
        public float $credit_limit = 0,
        public int $payment_terms = 30,
        public ?string $address = null,
        public ?string $city = null,
        public string $country = 'AE',
        public ?string $notes = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            contact_person: $data['contact_person'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            tax_registration_number: $data['tax_registration_number'] ?? null,
            credit_limit: (float) ($data['credit_limit'] ?? 0),
            payment_terms: (int) ($data['payment_terms'] ?? 30),
            address: $data['address'] ?? null,
            city: $data['city'] ?? null,
            country: $data['country'] ?? 'AE',
            notes: $data['notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter(get_object_vars($this), fn ($v) => $v !== null);
    }
}
