<?php

namespace App\DTOs;

readonly class ParsedOrderDTO
{
    /**
     * @param ParsedOrderItemDTO[] $items
     * @param string[] $warnings
     */
    public function __construct(
        public string $raw_message,
        public ?string $customer_name = null,
        public ?int $client_id = null,
        public float $customer_confidence = 0,
        public array $items = [],
        public ?string $delivery_date = null,
        public ?string $delivery_raw = null,
        public array $warnings = [],
    ) {}

    public function toArray(): array
    {
        return [
            'raw_message' => $this->raw_message,
            'customer_name' => $this->customer_name,
            'client_id' => $this->client_id,
            'customer_confidence' => $this->customer_confidence,
            'items' => array_map(fn (ParsedOrderItemDTO $i) => $i->toArray(), $this->items),
            'delivery_date' => $this->delivery_date,
            'delivery_raw' => $this->delivery_raw,
            'warnings' => $this->warnings,
            'has_warnings' => count($this->warnings) > 0,
            'item_count' => count($this->items),
        ];
    }
}
