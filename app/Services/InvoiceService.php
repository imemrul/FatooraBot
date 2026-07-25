<?php

namespace App\Services;

use App\DTOs\InvoiceDTO;
use App\Events\InvoiceCreated;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    private const ALLOWED_TRANSITIONS = [
        'draft' => ['sent', 'cancelled'],
        'sent' => ['paid', 'overdue', 'cancelled'],
        'overdue' => ['paid', 'cancelled'],
        'paid' => [],
        'cancelled' => [],
    ];

    public function __construct(
        private readonly InvoiceRepositoryInterface $repository,
    ) {}

    public function list(int $perPage = 15, ?string $status = null): LengthAwarePaginator
    {
        $query = Invoice::with(['client', 'creator', 'items'])
            ->latest();

        if ($status) {
            $query->where('status', $status);
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): Invoice
    {
        $invoice = $this->repository->findOrFail($id);
        $invoice->load(['client', 'creator', 'items.product', 'payments.recorder', 'company']);

        return $invoice;
    }

    public function create(InvoiceDTO $dto): Invoice
    {
        return DB::transaction(function () use ($dto) {
            $invoice = $this->repository->create([
                'client_id' => $dto->client_id,
                'created_by' => Auth::id(),
                'invoice_number' => $this->generateInvoiceNumber(),
                'issue_date' => $dto->issue_date,
                'due_date' => $dto->due_date,
                'discount' => $dto->discount,
                'currency' => $dto->currency,
                'status' => $dto->status,
                'notes' => $dto->notes,
            ]);

            foreach ($dto->items as $itemDto) {
                $invoice->items()->create([
                    'product_id' => $itemDto->product_id,
                    'description' => $itemDto->description,
                    'quantity' => $itemDto->quantity,
                    'unit_price' => $itemDto->unit_price,
                    'vat_rate' => $itemDto->vat_rate,
                ]);
            }

            $invoice->recalculateTotals();
            $invoice->load(['client', 'creator', 'items']);

            InvoiceCreated::dispatch($invoice);

            return $invoice;
        });
    }

    public function update(Invoice $invoice, InvoiceDTO $dto): Invoice
    {
        return DB::transaction(function () use ($invoice, $dto) {
            $this->repository->update($invoice, [
                'client_id' => $dto->client_id,
                'issue_date' => $dto->issue_date,
                'due_date' => $dto->due_date,
                'discount' => $dto->discount,
                'currency' => $dto->currency,
                'notes' => $dto->notes,
            ]);

            $invoice->items()->delete();

            foreach ($dto->items as $itemDto) {
                $invoice->items()->create([
                    'product_id' => $itemDto->product_id,
                    'description' => $itemDto->description,
                    'quantity' => $itemDto->quantity,
                    'unit_price' => $itemDto->unit_price,
                    'vat_rate' => $itemDto->vat_rate,
                ]);
            }

            $invoice->recalculateTotals();
            $invoice->load(['client', 'creator', 'items', 'payments']);

            return $invoice;
        });
    }

    public function delete(Invoice $invoice): bool
    {
        return $this->repository->delete($invoice);
    }

    public function markAs(Invoice $invoice, string $newStatus): Invoice
    {
        $this->validateTransition($invoice->status, $newStatus);

        $this->repository->update($invoice, ['status' => $newStatus]);

        return $invoice->fresh(['client', 'creator', 'items', 'payments']);
    }

    public function sendInvoice(Invoice $invoice): Invoice
    {
        $this->validateTransition($invoice->status, 'sent');

        $this->repository->update($invoice, ['status' => 'sent']);

        return $invoice->fresh(['client', 'creator', 'items', 'payments']);
    }

    public function recordPayment(Invoice $invoice, array $data): InvoicePayment
    {
        return DB::transaction(function () use ($invoice, $data) {
            $payment = $invoice->payments()->create([
                'company_id' => $invoice->company_id,
                'recorded_by' => Auth::id(),
                'amount' => $data['amount'],
                'method' => $data['method'],
                'reference' => $data['reference'] ?? null,
                'payment_date' => $data['payment_date'],
                'notes' => $data['notes'] ?? null,
            ]);

            $invoice->syncPaidAmount();

            return $payment->load('recorder');
        });
    }

    public function generatePdf(Invoice $invoice): \Barryvdh\DomPDF\PDF
    {
        $invoice->load(['company', 'client', 'items', 'payments']);

        $qrData = base64_encode($invoice->generateQrData());

        return Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'company' => $invoice->company,
            'client' => $invoice->client,
            'items' => $invoice->items,
            'payments' => $invoice->payments,
            'qrData' => $qrData,
        ])->setPaper('a4');
    }

    private function validateTransition(string $from, string $to): void
    {
        $allowed = self::ALLOWED_TRANSITIONS[$from] ?? [];

        if (!in_array($to, $allowed)) {
            throw ValidationException::withMessages([
                'status' => ["Cannot transition from '{$from}' to '{$to}'."],
            ]);
        }
    }

    private function generateInvoiceNumber(): string
    {
        $companyId = Auth::user()->company_id;
        $prefix = 'INV-' . str_pad($companyId, 4, '0', STR_PAD_LEFT);

        // Lock to prevent race condition on concurrent requests
        $lastNumber = DB::table('invoices')
            ->where('company_id', $companyId)
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('invoice_number');

        $sequence = $lastNumber
            ? ((int) substr($lastNumber, -6)) + 1
            : 1;

        return $prefix . '-' . str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }
}
