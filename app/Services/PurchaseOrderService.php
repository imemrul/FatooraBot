<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    public function __construct(private readonly InventoryService $inventoryService) {}

    // ── Suppliers ──
    public function listSuppliers(?string $search = null): LengthAwarePaginator
    {
        $query = Supplier::withCount('purchaseOrders')->latest();
        if ($search) {
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $search);
            $query->where('name', 'ilike', "%{$escaped}%");
        }
        return $query->paginate(20);
    }

    public function createSupplier(array $data): Supplier { return Supplier::create($data); }
    public function updateSupplier(Supplier $s, array $data): Supplier { $s->update($data); return $s->fresh(); }
    public function deleteSupplier(Supplier $s): void { $s->delete(); }

    // ── Purchase Orders ──
    public function list(?string $status = null): LengthAwarePaginator
    {
        $query = PurchaseOrder::with(['supplier:id,name', 'warehouse:id,name', 'creator:id,name'])->latest();
        if ($status) $query->where('status', $status);
        return $query->paginate(20);
    }

    public function show(PurchaseOrder $po): PurchaseOrder
    {
        return $po->load(['supplier', 'warehouse', 'creator:id,name', 'items.product:id,name,sku']);
    }

    public function create(array $data, array $items): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $items) {
            $data['created_by'] = Auth::id();
            $data['po_number'] = $this->generateNumber();
            $po = PurchaseOrder::create($data);
            $this->syncItems($po, $items);
            $po->recalculate();
            return $po->load(['supplier:id,name', 'items']);
        });
    }

    public function update(PurchaseOrder $po, array $data, array $items): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $data, $items) {
            $po->update($data);
            $this->syncItems($po, $items);
            $po->recalculate();
            return $po->fresh()->load(['supplier:id,name', 'items']);
        });
    }

    public function delete(PurchaseOrder $po): void { $po->delete(); }

    public function send(PurchaseOrder $po): PurchaseOrder
    {
        $po->update(['status' => 'sent']);
        return $po->fresh();
    }

    public function receiveGoods(PurchaseOrder $po, array $receivedItems): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $receivedItems) {
            $allReceived = true;

            foreach ($receivedItems as $ri) {
                $poItem = $po->items()->findOrFail($ri['item_id']);
                $qty = (float) $ri['quantity'];
                $poItem->increment('received_quantity', $qty);

                if (!$poItem->fresh()->isFullyReceived()) $allReceived = false;

                // Auto stock-in if product and warehouse exist
                if ($poItem->product_id && $po->warehouse_id) {
                    $this->inventoryService->stockIn(
                        new \App\DTOs\StockMovementDTO(
                            product_id: $poItem->product_id,
                            warehouse_id: $po->warehouse_id,
                            quantity: (int) $qty,
                            type: 'stock_in',
                            reference: "PO: {$po->po_number}",
                        )
                    );
                }
            }

            $po->update(['status' => $allReceived ? 'received' : 'partial']);
            return $po->fresh()->load(['supplier:id,name', 'items.product:id,name']);
        });
    }

    public function cancel(PurchaseOrder $po): PurchaseOrder
    {
        $po->update(['status' => 'cancelled']);
        return $po->fresh();
    }

    private function syncItems(PurchaseOrder $po, array $items): void
    {
        $po->items()->delete();
        foreach ($items as $item) {
            $lineTotal = $item['quantity'] * $item['unit_price'];
            $vatAmount = round($lineTotal * ($item['vat_rate'] ?? 5) / 100, 2);
            $po->items()->create([
                'product_id' => $item['product_id'] ?? null,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'vat_rate' => $item['vat_rate'] ?? 5,
                'vat_amount' => $vatAmount,
                'line_total' => $lineTotal,
            ]);
        }
    }

    private function generateNumber(): string
    {
        $companyId = Auth::user()->company_id;
        $prefix = 'PO-' . str_pad($companyId, 4, '0', STR_PAD_LEFT);
        $last = DB::table('purchase_orders')->where('company_id', $companyId)->lockForUpdate()->orderByDesc('id')->value('po_number');
        $seq = $last ? ((int) substr($last, -6)) + 1 : 1;
        return $prefix . '-' . str_pad($seq, 6, '0', STR_PAD_LEFT);
    }
}
