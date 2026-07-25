<?php

namespace App\DTOs;

readonly class InvoiceItemDTO
{
    public function __construct(
        public string $description,
        public float $quantity,
        public float $unit_price,
        public float $vat_rate = 5.00,
        public ?int $product_id = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            description: $data['description'],
            quantity: (float) $data['quantity'],
            unit_price: (float) $data['unit_price'],
            vat_rate: (float) ($data['vat_rate'] ?? 5.00),
            product_id: isset($data['product_id']) ? (int) $data['product_id'] : null,
        );
    }

    public function toArray(): array
    {
        return array_filter(get_object_vars($this), fn ($v) => $v !== null);
    }
}
