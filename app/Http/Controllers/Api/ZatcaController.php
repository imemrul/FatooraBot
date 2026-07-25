<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\ZatcaService;
use Illuminate\Http\JsonResponse;

class ZatcaController extends Controller
{
    public function __construct(private readonly ZatcaService $service) {}

    public function generate(Invoice $invoice): JsonResponse
    {
        $invoice = $this->service->generatePhase1($invoice);
        return response()->json([
            'message' => 'ZATCA Phase 1 data generated.',
            'uuid' => $invoice->uuid,
            'zatca_status' => $invoice->zatca_status,
            'qr_tlv' => $invoice->zatca_qr_tlv,
            'hash' => $invoice->zatca_hash,
        ]);
    }

    public function xml(Invoice $invoice): \Illuminate\Http\Response
    {
        if (!$invoice->zatca_xml) {
            $this->service->generatePhase1($invoice);
            $invoice->refresh();
        }

        return response($invoice->zatca_xml, 200, ['Content-Type' => 'application/xml']);
    }
}
