<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRecurringInvoiceRequest;
use App\Models\RecurringInvoice;
use App\Services\RecurringInvoiceService;
use Illuminate\Http\JsonResponse;

class RecurringInvoiceController extends Controller
{
    public function __construct(private readonly RecurringInvoiceService $service) {}

    public function index(): JsonResponse
    {
        return response()->json($this->service->list());
    }

    public function show(RecurringInvoice $recurringInvoice): JsonResponse
    {
        return response()->json(['recurring_invoice' => $this->service->show($recurringInvoice)]);
    }

    public function store(StoreRecurringInvoiceRequest $request): JsonResponse
    {
        $data = $request->safe()->except('items');
        $data['created_by'] = $request->user()->id;
        $data['next_issue_date'] = $data['start_date'];

        $ri = $this->service->create($data, $request->validated('items'));
        return response()->json(['recurring_invoice' => $ri, 'message' => 'Recurring invoice created.'], 201);
    }

    public function update(StoreRecurringInvoiceRequest $request, RecurringInvoice $recurringInvoice): JsonResponse
    {
        $ri = $this->service->update($recurringInvoice, $request->safe()->except('items'), $request->validated('items'));
        return response()->json(['recurring_invoice' => $ri, 'message' => 'Updated.']);
    }

    public function destroy(RecurringInvoice $recurringInvoice): JsonResponse
    {
        $this->service->delete($recurringInvoice);
        return response()->json(['message' => 'Deleted.']);
    }

    public function toggleActive(RecurringInvoice $recurringInvoice): JsonResponse
    {
        $ri = $this->service->toggleActive($recurringInvoice);
        return response()->json(['recurring_invoice' => $ri]);
    }
}
