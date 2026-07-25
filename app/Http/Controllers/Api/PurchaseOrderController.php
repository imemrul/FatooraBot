<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\PurchaseOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function __construct(private readonly PurchaseOrderService $service) {}

    // ── Suppliers ──
    public function suppliers(Request $request): JsonResponse
    {
        return response()->json($this->service->listSuppliers($request->query('search')));
    }

    public function storeSupplier(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255', 'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email', 'phone' => 'nullable|string|max:20',
            'tax_registration_number' => 'nullable|string', 'address' => 'nullable|string',
            'city' => 'nullable|string', 'country' => 'nullable|string|size:2',
        ]);
        return response()->json(['supplier' => $this->service->createSupplier($data)], 201);
    }

    public function updateSupplier(Request $request, Supplier $supplier): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255', 'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email', 'phone' => 'nullable|string|max:20',
            'tax_registration_number' => 'nullable|string', 'address' => 'nullable|string',
            'city' => 'nullable|string', 'country' => 'nullable|string|size:2',
        ]);
        return response()->json(['supplier' => $this->service->updateSupplier($supplier, $data)]);
    }

    public function destroySupplier(Supplier $supplier): JsonResponse
    {
        $this->service->deleteSupplier($supplier);
        return response()->json(['message' => 'Deleted.']);
    }

    // ── Purchase Orders ──
    public function index(Request $request): JsonResponse
    {
        return response()->json($this->service->list($request->query('status')));
    }

    public function show(PurchaseOrder $purchaseOrder): JsonResponse
    {
        return response()->json(['purchase_order' => $this->service->show($purchaseOrder)]);
    }

    public function store(Request $request): JsonResponse
    {
        $cid = $request->user()->company_id;
        $request->validate([
            'supplier_id' => "required|exists:suppliers,id,company_id,{$cid}",
            'warehouse_id' => "nullable|exists:warehouses,id,company_id,{$cid}",
            'order_date' => 'required|date', 'expected_date' => 'nullable|date',
            'notes' => 'nullable|string', 'items' => 'required|array|min:1',
            'items.*.description' => 'required|string', 'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0', 'items.*.vat_rate' => 'nullable|numeric',
            'items.*.product_id' => "nullable|exists:products,id,company_id,{$cid}",
        ]);
        $po = $this->service->create($request->except('items'), $request->items);
        return response()->json(['purchase_order' => $po, 'message' => 'PO created.'], 201);
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $cid = $request->user()->company_id;
        $request->validate([
            'supplier_id' => "required|exists:suppliers,id,company_id,{$cid}",
            'warehouse_id' => "nullable|exists:warehouses,id,company_id,{$cid}",
            'order_date' => 'required|date', 'expected_date' => 'nullable|date',
            'notes' => 'nullable|string', 'items' => 'required|array|min:1',
            'items.*.description' => 'required|string', 'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0', 'items.*.vat_rate' => 'nullable|numeric',
            'items.*.product_id' => "nullable|exists:products,id,company_id,{$cid}",
        ]);
        $po = $this->service->update($purchaseOrder, $request->except('items'), $request->items);
        return response()->json(['purchase_order' => $po]);
    }

    public function destroy(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->service->delete($purchaseOrder);
        return response()->json(['message' => 'Deleted.']);
    }

    public function send(PurchaseOrder $purchaseOrder): JsonResponse
    {
        return response()->json(['purchase_order' => $this->service->send($purchaseOrder)]);
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|integer',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);
        $po = $this->service->receiveGoods($purchaseOrder, $request->items);
        return response()->json(['purchase_order' => $po, 'message' => 'Goods received and stock updated.']);
    }

    public function cancel(PurchaseOrder $purchaseOrder): JsonResponse
    {
        return response()->json(['purchase_order' => $this->service->cancel($purchaseOrder)]);
    }
}
