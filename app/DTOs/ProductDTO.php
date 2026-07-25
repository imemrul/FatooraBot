<?php

namespace App\DTOs;

readonly class ProductDTO
{
    public function __construct(
        public string $name,
        public float $unit_price,
        public ?string $sku = null,
        public ?string $barcode = null,
        public ?string $description = null,
        public float $cost_price = 0,
        public float $vat_rate = 5.00,
        public string $unit = 'unit',
        public int $low_stock_threshold = 10,
        public bool $is_active = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            unit_price: (float) $data['unit_price'],
            sku: $data['sku'] ?? null,
            barcode: $data['barcode'] ?? null,
            description: $data['description'] ?? null,
            cost_price: (float) ($data['cost_price'] ?? 0),
            vat_rate: (float) ($data['vat_rate'] ?? 5.00),
            unit: $data['unit'] ?? 'unit',
            low_stock_threshold: (int) ($data['low_stock_threshold'] ?? 10),
            is_active: $data['is_active'] ?? true,
        );
    }

    public function toArray(): array
    {
        return array_filter(get_object_vars($this), fn ($v) => $v !== null);
    }
}
