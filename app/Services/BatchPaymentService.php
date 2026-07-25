<?php

namespace App\Services;

use App\Models\BatchPayment;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BatchPaymentService
{
    public function list(?int $clientId = null): LengthAwarePaginator
    {
        $query = BatchPayment::with(['client:id,name', 'recorder:id,name', 'allocations.invoice:id,invoice_number'])
            ->latest();

        if ($clientId) $query->where('client_id', $clientId);

        return $query->paginate(20);
    }

    public function create(array $data, array $allocations): BatchPayment
    {
        return DB::transaction(function () use ($data, $allocations) {
            $data['recorded_by'] = Auth::id();
            $batch = BatchPayment::create($data);

            foreach ($allocations as $alloc) {
                $invoice = Invoice::findOrFail($alloc['invoice_id']);

                $payment = InvoicePayment::create([
                    'company_id' => $batch->company_id,
                    'invoice_id' => $invoice->id,
                    'recorded_by' => Auth::id(),
                    'amount' => $alloc['amount'],
                    'method' => $data['method'],
                    'reference' => $data['reference'] ?? "BATCH-{$batch->id}",
                    'payment_date' => $data['payment_date'],
                    'notes' => "Batch payment #{$batch->id}",
                ]);

                $batch->allocations()->create([
                    'invoice_id' => $invoice->id,
                    'invoice_payment_id' => $payment->id,
                    'amount' => $alloc['amount'],
                ]);

                $invoice->syncPaidAmount();
            }

            return $batch->load(['client:id,name', 'allocations.invoice:id,invoice_number']);
        });
    }

    /**
     * Get unpaid invoices for a client (for allocation UI).
     */
    public function getUnpaidInvoices(int $clientId): \Illuminate\Database\Eloquent\Collection
    {
        return Invoice::where('client_id', $clientId)
            ->whereNotIn('status', ['draft', 'cancelled', 'paid'])
            ->whereRaw('total > paid_amount')
            ->orderBy('due_date')
            ->get(['id', 'invoice_number', 'due_date', 'total', 'paid_amount', 'status']);
    }
}
