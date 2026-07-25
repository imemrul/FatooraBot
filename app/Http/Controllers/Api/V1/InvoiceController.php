<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\InvoiceDTO;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\InvoiceResource;
use App\Models\Invoice;
use App\Services\InvoiceService;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $service,
        private readonly WebhookService $webhooks,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $invoices = Invoice::with(['client', 'items'])
            ->latest()
            ->when($request->has('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->has('customer_id'), fn ($q) => $q->where('client_id', $request->input('customer_id')))
            ->paginate($request->integer('per_page', 25));

        return InvoiceResource::collection($invoices)->response();
    }

    public function show(Invoice $invoice): InvoiceResource
    {
        $invoice->load(['client', 'items', 'payments']);

        return new InvoiceResource($invoice);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:clients,id'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.description' => ['required', 'string', 'max:500'],
            'line_items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'line_items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'line_items.*.vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'line_items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
        ]);

        // Map external field names to internal
        $dtoData = [
            'client_id' => $validated['customer_id'],
            'issue_date' => $validated['issue_date'],
            'due_date' => $validated['due_date'],
            'discount' => $validated['discount'] ?? 0,
            'currency' => $validated['currency'] ?? 'AED',
            'notes' => $validated['notes'] ?? null,
            'items' => $validated['line_items'],
        ];

        $invoice = $this->service->create(InvoiceDTO::fromArray($dtoData));

        $this->webhooks->dispatch(
            $request->user()->company_id,
            'invoice.created',
            (new InvoiceResource($invoice->load(['client', 'items'])))->resolve(),
        );

        return (new InvoiceResource($invoice->load(['client', 'items'])))->response()->setStatusCode(201);
    }
}
