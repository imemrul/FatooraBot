<?php

namespace App\Http\Controllers\Api;

use App\DTOs\InvoiceDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Http\Resources\InvoicePaymentResource;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    public function __construct(private readonly InvoiceService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Invoice::class);

        return InvoiceResource::collection(
            $this->service->list(
                $request->integer('per_page', 15),
                $request->string('status')->toString() ?: null,
            )
        );
    }

    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $invoice = $this->service->create(
            InvoiceDTO::fromArray($request->validated())
        );

        return (new InvoiceResource($invoice))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Invoice $invoice): InvoiceResource
    {
        $this->authorize('view', $invoice);

        return new InvoiceResource($this->service->find($invoice->id));
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): InvoiceResource
    {
        $this->authorize('update', $invoice);

        $updated = $this->service->update(
            $invoice,
            InvoiceDTO::fromArray($request->validated())
        );

        return new InvoiceResource($updated);
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        $this->authorize('delete', $invoice);

        $this->service->delete($invoice);

        return response()->json(null, 204);
    }

    public function markAs(Request $request, Invoice $invoice): InvoiceResource
    {
        $this->authorize('update', $invoice);

        $request->validate([
            'status' => ['required', 'in:draft,sent,paid,overdue,cancelled'],
        ]);

        $updated = $this->service->markAs($invoice, $request->input('status'));

        return new InvoiceResource($updated);
    }

    public function send(Invoice $invoice): InvoiceResource
    {
        $this->authorize('update', $invoice);

        $updated = $this->service->sendInvoice($invoice);

        return new InvoiceResource($updated);
    }

    public function recordPayment(StorePaymentRequest $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('update', $invoice);

        $payment = $this->service->recordPayment($invoice, $request->validated());

        return response()->json([
            'payment' => new InvoicePaymentResource($payment),
            'invoice' => new InvoiceResource($invoice->fresh(['client', 'creator', 'items', 'payments.recorder'])),
        ], 201);
    }

    public function downloadPdf(Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);

        $pdf = $this->service->generatePdf($invoice);

        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }

    public function all(): JsonResponse
    {
        $invoices = Invoice::with('client:id,name')
            ->orderByDesc('id')
            ->get(['id', 'client_id', 'invoice_number', 'total', 'paid_amount', 'status']);

        return response()->json(['data' => $invoices]);
    }

    // ── Bulk Actions ──

    public function bulkSend(Request $request): JsonResponse
    {
        $request->validate(['ids' => 'required|array|min:1', 'ids.*' => 'integer']);
        $count = 0;
        Invoice::whereIn('id', $request->ids)->where('status', 'draft')->each(function ($inv) use (&$count) {
            $this->service->sendInvoice($inv);
            $count++;
        });
        return response()->json(['message' => "{$count} invoice(s) sent."]);
    }

    public function bulkStatus(Request $request): JsonResponse
    {
        $request->validate(['ids' => 'required|array|min:1', 'ids.*' => 'integer', 'status' => 'required|string']);
        $count = 0;
        Invoice::whereIn('id', $request->ids)->each(function ($inv) use ($request, &$count) {
            try { $this->service->markAs($inv, $request->status); $count++; } catch (\Exception) {}
        });
        return response()->json(['message' => "{$count} invoice(s) updated."]);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate(['ids' => 'required|array|min:1', 'ids.*' => 'integer']);
        $count = Invoice::whereIn('id', $request->ids)->where('status', 'draft')->delete();
        return response()->json(['message' => "{$count} draft invoice(s) deleted."]);
    }
}
