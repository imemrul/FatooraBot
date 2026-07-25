<?php

namespace App\DTOs;

readonly class ParsedOrderItemDTO
{
    public function __construct(
        public string $raw_text,
        public int $quantity,
        public string $product_name,
        public ?int $product_id = null,
        public ?float $unit_price = null,
        public ?float $vat_rate = null,
        public float $confidence = 0,
    ) {}

    public function toArray(): array
    {
        return [
            'raw_text' => $this->raw_text,
            'quantity' => $this->quantity,
            'product_name' => $this->product_name,
            'product_id' => $this->product_id,
            'unit_price' => $this->unit_price,
            'vat_rate' => $this->vat_rate,
            'confidence' => $this->confidence,
        ];
    }
}
