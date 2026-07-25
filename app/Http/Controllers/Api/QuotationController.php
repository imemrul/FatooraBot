<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use App\Services\QuotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuotationController extends Controller
{
    public function __construct(private readonly QuotationService $service) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->service->list($request->query('status')));
    }

    public function show(Quotation $quotation): JsonResponse
    {
        return response()->json(['quotation' => $this->service->show($quotation)]);
    }

    public function store(Request $request): JsonResponse
    {
        $cid = $request->user()->company_id;
        $request->validate([
            'client_id' => "required|exists:clients,id,company_id,{$cid}",
            'issue_date' => 'required|date', 'valid_until' => 'required|date|after:issue_date',
            'discount' => 'nullable|numeric|min:0', 'currency' => 'nullable|string|size:3',
            'notes' => 'nullable|string', 'terms' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string', 'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0', 'items.*.vat_rate' => 'nullable|numeric',
            'items.*.product_id' => "nullable|exists:products,id,company_id,{$cid}",
        ]);
        $q = $this->service->create($request->except('items'), $request->items);
        return response()->json(['quotation' => $q, 'message' => 'Quotation created.'], 201);
    }

    public function update(Request $request, Quotation $quotation): JsonResponse
    {
        $cid = $request->user()->company_id;
        $request->validate([
            'client_id' => "required|exists:clients,id,company_id,{$cid}",
            'issue_date' => 'required|date', 'valid_until' => 'required|date',
            'discount' => 'nullable|numeric|min:0', 'notes' => 'nullable|string', 'terms' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string', 'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0', 'items.*.vat_rate' => 'nullable|numeric',
            'items.*.product_id' => "nullable|exists:products,id,company_id,{$cid}",
        ]);
        $q = $this->service->update($quotation, $request->except('items'), $request->items);
        return response()->json(['quotation' => $q]);
    }

    public function destroy(Quotation $quotation): JsonResponse
    {
        $this->service->delete($quotation);
        return response()->json(['message' => 'Deleted.']);
    }

    public function send(Quotation $quotation): JsonResponse
    {
        return response()->json(['quotation' => $this->service->markAs($quotation, 'sent')]);
    }

    public function approve(Quotation $quotation): JsonResponse
    {
        return response()->json(['quotation' => $this->service->markAs($quotation, 'approved')]);
    }

    public function reject(Quotation $quotation): JsonResponse
    {
        return response()->json(['quotation' => $this->service->markAs($quotation, 'rejected')]);
    }

    public function convertToInvoice(Quotation $quotation): JsonResponse
    {
        $invoice = $this->service->convertToInvoice($quotation);
        return response()->json(['invoice' => $invoice, 'message' => 'Quotation converted to invoice.']);
    }
}
