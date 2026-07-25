<?php

namespace App\DTOs;

readonly class SalesOrderDTO
{
    /** @param SalesOrderItemDTO[] $items */
    public function __construct(
        public int $client_id,
        public string $order_date,
        public ?string $delivery_date = null,
        public ?int $warehouse_id = null,
        public string $currency = 'AED',
        public string $status = 'draft',
        public ?string $notes = null,
        public array $items = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            client_id: (int) $data['client_id'],
            order_date: $data['order_date'],
            delivery_date: $data['delivery_date'] ?? null,
            warehouse_id: isset($data['warehouse_id']) ? (int) $data['warehouse_id'] : null,
            currency: $data['currency'] ?? 'AED',
            status: $data['status'] ?? 'draft',
            notes: $data['notes'] ?? null,
            items: array_map(fn (array $i) => SalesOrderItemDTO::fromArray($i), $data['items']),
        );
    }
}
