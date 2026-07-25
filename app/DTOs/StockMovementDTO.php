<?php

namespace App\DTOs;

readonly class StockMovementDTO
{
    public function __construct(
        public int $product_id,
        public int $warehouse_id,
        public string $type,
        public int $quantity,
        public ?int $to_warehouse_id = null,
        public ?string $reference = null,
        public ?string $notes = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            product_id: (int) $data['product_id'],
            warehouse_id: (int) $data['warehouse_id'],
            type: $data['type'],
            quantity: (int) $data['quantity'],
            to_warehouse_id: isset($data['to_warehouse_id']) ? (int) $data['to_warehouse_id'] : null,
            reference: $data['reference'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }
}
