<?php

namespace App\Services;

use App\Models\CreditNote;
use App\Models\Invoice;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreditNoteService
{
    public function list(?int $invoiceId = null): LengthAwarePaginator
    {
        $query = CreditNote::with(['invoice:id,invoice_number', 'client:id,name', 'creator:id,name'])->latest();
        if ($invoiceId) $query->where('invoice_id', $invoiceId);
        return $query->paginate(20);
    }

    public function show(CreditNote $cn): CreditNote
    {
        return $cn->load(['invoice:id,invoice_number,total', 'client', 'creator:id,name', 'items.product:id,name']);
    }

    public function create(array $data, array $items): CreditNote
    {
        return DB::transaction(function () use ($data, $items) {
            $data['created_by'] = Auth::id();
            $data['credit_note_number'] = $this->generateNumber();
            $cn = CreditNote::create($data);
            $this->syncItems($cn, $items);
            $cn->recalculate();
            return $cn->load(['invoice:id,invoice_number', 'client:id,name', 'items']);
        });
    }

    public function issue(CreditNote $cn): CreditNote
    {
        $cn->update(['status' => 'issued']);
        return $cn->fresh();
    }

    public function apply(CreditNote $cn): CreditNote
    {
        return DB::transaction(function () use ($cn) {
            $invoice = $cn->invoice;
            $invoice->payments()->create([
                'company_id' => $cn->company_id,
                'recorded_by' => Auth::id(),
                'amount' => $cn->total,
                'method' => 'credit_note',
                'reference' => $cn->credit_note_number,
                'payment_date' => now()->toDateString(),
                'notes' => "Applied credit note {$cn->credit_note_number}",
            ]);
            $invoice->syncPaidAmount();
            $cn->update(['status' => 'applied']);
            return $cn->fresh();
        });
    }

    public function cancel(CreditNote $cn): CreditNote
    {
        $cn->update(['status' => 'cancelled']);
        return $cn->fresh();
    }

    private function syncItems(CreditNote $cn, array $items): void
    {
        $cn->items()->delete();
        foreach ($items as $item) {
            $lineTotal = $item['quantity'] * $item['unit_price'];
            $vatAmount = round($lineTotal * ($item['vat_rate'] ?? 5) / 100, 2);
            $cn->items()->create([
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
        $prefix = 'CN-' . str_pad($companyId, 4, '0', STR_PAD_LEFT);
        $last = DB::table('credit_notes')->where('company_id', $companyId)->lockForUpdate()->orderByDesc('id')->value('credit_note_number');
        $seq = $last ? ((int) substr($last, -6)) + 1 : 1;
        return $prefix . '-' . str_pad($seq, 6, '0', STR_PAD_LEFT);
    }
}
