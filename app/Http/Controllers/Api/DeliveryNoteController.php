<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeliveryNote;
use App\Models\SalesOrder;
use App\Services\DeliveryNoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryNoteController extends Controller
{
    public function __construct(private readonly DeliveryNoteService $service) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->service->list($request->query('status')));
    }

    public function show(DeliveryNote $deliveryNote): JsonResponse
    {
        return response()->json(['delivery_note' => $this->service->show($deliveryNote)]);
    }

    public function store(Request $request): JsonResponse
    {
        $cid = $request->user()->company_id;
        $request->validate([
            'client_id' => "required|exists:clients,id,company_id,{$cid}",
            'sales_order_id' => "nullable|exists:sales_orders,id,company_id,{$cid}",
            'delivery_date' => 'required|date', 'driver_name' => 'nullable|string|max:100',
            'vehicle_number' => 'nullable|string|max:50', 'delivery_address' => 'nullable|string',
            'notes' => 'nullable|string', 'items' => 'required|array|min:1',
            'items.*.description' => 'required|string', 'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.product_id' => 'nullable|integer', 'items.*.unit' => 'nullable|string',
        ]);
        $dn = $this->service->create($request->except('items'), $request->items);
        return response()->json(['delivery_note' => $dn, 'message' => 'Delivery note created.'], 201);
    }

    public function createFromOrder(SalesOrder $salesOrder): JsonResponse
    {
        $dn = $this->service->createFromSalesOrder($salesOrder);
        return response()->json(['delivery_note' => $dn, 'message' => 'Delivery note generated from order.'], 201);
    }

    public function markInTransit(DeliveryNote $deliveryNote): JsonResponse
    {
        return response()->json(['delivery_note' => $this->service->markInTransit($deliveryNote)]);
    }

    public function markDelivered(DeliveryNote $deliveryNote): JsonResponse
    {
        return response()->json(['delivery_note' => $this->service->markDelivered($deliveryNote)]);
    }

    public function cancel(DeliveryNote $deliveryNote): JsonResponse
    {
        return response()->json(['delivery_note' => $this->service->cancel($deliveryNote)]);
    }

    public function destroy(DeliveryNote $deliveryNote): JsonResponse
    {
        $this->service->delete($deliveryNote);
        return response()->json(['message' => 'Deleted.']);
    }
}
