<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\RecurringInvoice;
use App\Models\RecurringInvoiceItem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class RecurringInvoiceService
{
    public function __construct(private readonly InvoiceService $invoiceService) {}

    public function list(): LengthAwarePaginator
    {
        return RecurringInvoice::with(['client:id,name', 'creator:id,name'])
            ->withCount('items')
            ->latest()
            ->paginate(20);
    }

    public function show(RecurringInvoice $ri): RecurringInvoice
    {
        return $ri->load(['client', 'creator:id,name', 'items.product:id,name']);
    }

    public function create(array $data, array $items): RecurringInvoice
    {
        return DB::transaction(function () use ($data, $items) {
            $ri = RecurringInvoice::create($data);
            $this->syncItems($ri, $items);
            $ri->recalculate();
            return $ri->load(['client:id,name', 'items']);
        });
    }

    public function update(RecurringInvoice $ri, array $data, array $items): RecurringInvoice
    {
        return DB::transaction(function () use ($ri, $data, $items) {
            $ri->update($data);
            $this->syncItems($ri, $items);
            $ri->recalculate();
            return $ri->fresh()->load(['client:id,name', 'items']);
        });
    }

    public function delete(RecurringInvoice $ri): void
    {
        $ri->delete();
    }

    public function toggleActive(RecurringInvoice $ri): RecurringInvoice
    {
        $ri->update(['is_active' => !$ri->is_active]);
        return $ri->fresh();
    }

    /**
     * Cron: generate invoices for all due recurring templates.
     */
    public function processAllDue(): int
    {
        $count = 0;

        RecurringInvoice::where('is_active', true)
            ->where('next_issue_date', '<=', now()->toDateString())
            ->with(['items', 'client'])
            ->chunk(50, function ($templates) use (&$count) {
                foreach ($templates as $ri) {
                    $this->generateInvoice($ri);
                    $count++;
                }
            });

        return $count;
    }

    private function generateInvoice(RecurringInvoice $ri): void
    {
        $items = $ri->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'description' => $item->description,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
            'vat_rate' => $item->vat_rate,
        ])->toArray();

        $invoiceData = [
            'client_id' => $ri->client_id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays($ri->payment_terms)->toDateString(),
            'currency' => $ri->currency,
            'discount' => $ri->discount,
            'notes' => $ri->notes,
            'status' => 'draft',
        ];

        $this->invoiceService->create($invoiceData, $items, $ri->created_by);

        $ri->advanceNextDate();

        // Notify creator
        AppNotification::send(
            $ri->company_id, $ri->created_by, 'recurring_invoice',
            'Recurring invoice generated',
            "Invoice for {$ri->client->name} was auto-generated.",
            '/invoices'
        );
    }

    private function syncItems(RecurringInvoice $ri, array $items): void
    {
        $ri->items()->delete();

        foreach ($items as $item) {
            $lineTotal = $item['quantity'] * $item['unit_price'];
            $vatAmount = round($lineTotal * ($item['vat_rate'] ?? 5) / 100, 2);

            $ri->items()->create([
                'product_id' => $item['product_id'] ?? null,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'vat_rate' => $item['vat_rate'] ?? 5,
                'vat_amount' => $vatAmount,
                'line_total' => $lineTotal,
            ]);
        }
    }
}
