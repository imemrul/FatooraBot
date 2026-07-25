<?php

namespace App\DTOs;

readonly class InvoiceDTO
{
    /**
     * @param InvoiceItemDTO[] $items
     */
    public function __construct(
        public int $client_id,
        public string $issue_date,
        public string $due_date,
        public array $items,
        public float $discount = 0,
        public string $currency = 'AED',
        public string $status = 'draft',
        public ?string $notes = null,
    ) {}

    public static function fromArray(array $data): self
    {
        $items = array_map(
            fn (array $item) => InvoiceItemDTO::fromArray($item),
            $data['items']
        );

        return new self(
            client_id: (int) $data['client_id'],
            issue_date: $data['issue_date'],
            due_date: $data['due_date'],
            items: $items,
            discount: (float) ($data['discount'] ?? 0),
            currency: $data['currency'] ?? 'AED',
            status: $data['status'] ?? 'draft',
            notes: $data['notes'] ?? null,
        );
    }
}
