<?php

namespace App\Services;

use App\DTOs\InvoiceDTO;
use App\Models\Quotation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuotationService
{
    public function __construct(private readonly InvoiceService $invoiceService) {}

    public function list(?string $status = null): LengthAwarePaginator
    {
        $query = Quotation::with(['client:id,name', 'creator:id,name'])->latest();
        if ($status) $query->where('status', $status);
        return $query->paginate(20);
    }

    public function show(Quotation $q): Quotation
    {
        return $q->load(['client', 'creator:id,name', 'items.product:id,name', 'invoice:id,invoice_number']);
    }

    public function create(array $data, array $items): Quotation
    {
        return DB::transaction(function () use ($data, $items) {
            $data['created_by'] = Auth::id();
            $data['quotation_number'] = $this->generateNumber();
            $q = Quotation::create($data);
            $this->syncItems($q, $items);
            $q->recalculate();
            return $q->load(['client:id,name', 'items']);
        });
    }

    public function update(Quotation $q, array $data, array $items): Quotation
    {
        return DB::transaction(function () use ($q, $data, $items) {
            $q->update($data);
            $this->syncItems($q, $items);
            $q->recalculate();
            return $q->fresh()->load(['client:id,name', 'items']);
        });
    }

    public function delete(Quotation $q): void { $q->delete(); }

    public function markAs(Quotation $q, string $status): Quotation
    {
        $q->update(['status' => $status]);
        return $q->fresh();
    }

    public function convertToInvoice(Quotation $q): \App\Models\Invoice
    {
        return DB::transaction(function () use ($q) {
            $items = $q->items->map(fn ($i) => [
                'product_id' => $i->product_id,
                'description' => $i->description,
                'quantity' => $i->quantity,
                'unit_price' => $i->unit_price,
                'vat_rate' => $i->vat_rate,
            ])->toArray();

            $dto = InvoiceDTO::fromArray([
                'client_id' => $q->client_id,
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'discount' => $q->discount,
                'currency' => $q->currency,
                'notes' => $q->notes,
                'items' => $items,
            ]);

            $invoice = $this->invoiceService->create($dto);
            $q->update(['status' => 'converted', 'invoice_id' => $invoice->id]);
            return $invoice;
        });
    }

    private function syncItems(Quotation $q, array $items): void
    {
        $q->items()->delete();
        foreach ($items as $item) {
            $lineTotal = $item['quantity'] * $item['unit_price'];
            $vatAmount = round($lineTotal * ($item['vat_rate'] ?? 5) / 100, 2);
            $q->items()->create([
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

    private function generateNumber(): string
    {
        $companyId = Auth::user()->company_id;
        $prefix = 'QT-' . str_pad($companyId, 4, '0', STR_PAD_LEFT);
        $last = DB::table('quotations')->where('company_id', $companyId)->lockForUpdate()->orderByDesc('id')->value('quotation_number');
        $seq = $last ? ((int) substr($last, -6)) + 1 : 1;
        return $prefix . '-' . str_pad($seq, 6, '0', STR_PAD_LEFT);
    }
}
