<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ClientPortalService;
use App\Services\InvoiceService;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;

class ClientPortalController extends Controller
{
    public function __construct(
        private readonly ClientPortalService $portalService,
        private readonly InvoiceService $invoiceService,
    ) {}

    public function generateLink(Invoice $invoice): JsonResponse
    {
        $link = $this->portalService->generateLink($invoice);
        return response()->json(['link' => $link]);
    }

    public function viewInvoice(string $token): JsonResponse
    {
        $data = $this->portalService->viewInvoice($token);
        if (!$data) return response()->json(['message' => 'Invalid or expired link.'], 404);
        return response()->json($data);
    }

    public function downloadPdf(string $token): mixed
    {
        $data = $this->portalService->viewInvoice($token);
        if (!$data) return response()->json(['message' => 'Invalid or expired link.'], 404);
        $pdf = $this->invoiceService->generatePdf($data['invoice']);
        return $pdf->download("invoice-{$data['invoice']->invoice_number}.pdf");
    }

    public function statement(string $token): JsonResponse
    {
        $data = $this->portalService->getStatement($token);
        if (!$data) return response()->json(['message' => 'Invalid or expired link.'], 404);
        return response()->json($data);
    }
}
