<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BatchPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BatchPaymentController extends Controller
{
    public function __construct(private readonly BatchPaymentService $service) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->service->list($request->query('client_id')));
    }

    public function store(Request $request): JsonResponse
    {
        $cid = $request->user()->company_id;
        $request->validate([
            'client_id' => "required|exists:clients,id,company_id,{$cid}",
            'total_amount' => 'required|numeric|min:0.01',
            'method' => 'required|string|max:50',
            'payment_date' => 'required|date',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'allocations' => 'required|array|min:1',
            'allocations.*.invoice_id' => "required|exists:invoices,id,company_id,{$cid}",
            'allocations.*.amount' => 'required|numeric|min:0.01',
        ]);

        $batch = $this->service->create($request->except('allocations'), $request->allocations);
        return response()->json(['batch_payment' => $batch, 'message' => 'Batch payment recorded.'], 201);
    }

    public function unpaidInvoices(Request $request, int $clientId): JsonResponse
    {
        return response()->json(['invoices' => $this->service->getUnpaidInvoices($clientId)]);
    }
}
