<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CreditNote;
use App\Services\CreditNoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreditNoteController extends Controller
{
    public function __construct(private readonly CreditNoteService $service) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->service->list($request->query('invoice_id')));
    }

    public function show(CreditNote $creditNote): JsonResponse
    {
        return response()->json(['credit_note' => $this->service->show($creditNote)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'invoice_id' => 'required|exists:invoices,id,company_id,' . $request->user()->company_id,
            'client_id' => 'required|exists:clients,id,company_id,' . $request->user()->company_id,
            'issue_date' => 'required|date',
            'reason' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string', 'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0', 'items.*.vat_rate' => 'nullable|numeric',
            'items.*.product_id' => 'nullable|integer',
        ]);
        $cn = $this->service->create($request->except('items'), $data['items']);
        return response()->json(['credit_note' => $cn, 'message' => 'Credit note created.'], 201);
    }

    public function issue(CreditNote $creditNote): JsonResponse
    {
        return response()->json(['credit_note' => $this->service->issue($creditNote)]);
    }

    public function apply(CreditNote $creditNote): JsonResponse
    {
        return response()->json(['credit_note' => $this->service->apply($creditNote), 'message' => 'Credit note applied to invoice.']);
    }

    public function cancel(CreditNote $creditNote): JsonResponse
    {
        return response()->json(['credit_note' => $this->service->cancel($creditNote)]);
    }
}
