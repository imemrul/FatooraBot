<?php

namespace App\Services;

use App\DTOs\InvoiceDTO;
use App\DTOs\InvoiceItemDTO;
use App\DTOs\SalesOrderDTO;
use App\DTOs\StockMovementDTO;
use App\Models\SalesOrder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesOrderService
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly InventoryService $inventoryService,
    ) {}

    public function list(int $perPage = 15, ?string $status = null): LengthAwarePaginator
    {
        $query = SalesOrder::with(['client', 'creator', 'warehouse', 'items'])
            ->latest();

        if ($status) {
            $query->where('status', $status);
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): SalesOrder
    {
        return SalesOrder::with(['client', 'creator', 'warehouse', 'items.product', 'invoice'])
            ->findOrFail($id);
    }

    public function create(SalesOrderDTO $dto): SalesOrder
    {
        return DB::transaction(function () use ($dto) {
            $order = SalesOrder::create([
                'client_id' => $dto->client_id,
                'created_by' => Auth::id(),
                'warehouse_id' => $dto->warehouse_id,
                'order_number' => $this->generateOrderNumber(),
                'order_date' => $dto->order_date,
                'delivery_date' => $dto->delivery_date,
                'currency' => $dto->currency,
                'status' => 'draft',
                'notes' => $dto->notes,
            ]);

            foreach ($dto->items as $item) {
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'vat_rate' => $item->vat_rate,
                ]);
            }

            $order->recalculateTotals();
            $order->load(['client', 'creator', 'warehouse', 'items']);

            return $order;
        });
    }

    public function update(SalesOrder $order, SalesOrderDTO $dto): SalesOrder
    {
        if ($order->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => ['Only draft orders can be edited.'],
            ]);
        }

        return DB::transaction(function () use ($order, $dto) {
            $order->update([
                'client_id' => $dto->client_id,
                'warehouse_id' => $dto->warehouse_id,
                'order_date' => $dto->order_date,
                'delivery_date' => $dto->delivery_date,
                'currency' => $dto->currency,
                'notes' => $dto->notes,
            ]);

            $order->items()->delete();

            foreach ($dto->items as $item) {
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'vat_rate' => $item->vat_rate,
                ]);
            }

            $order->recalculateTotals();
            $order->load(['client', 'creator', 'warehouse', 'items']);

            return $order;
        });
    }

    public function delete(SalesOrder $order): void
    {
        if ($order->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => ['Only draft orders can be deleted.'],
            ]);
        }

        $order->delete();
    }

    public function confirm(SalesOrder $order): SalesOrder
    {
        if ($order->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => ['Only draft orders can be confirmed.'],
            ]);
        }

        if (!$order->warehouse_id) {
            throw ValidationException::withMessages([
                'warehouse_id' => ['Select a warehouse before confirming.'],
            ]);
        }

        return DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                if (!$item->product_id) continue;

                $this->inventoryService->stockOut(new StockMovementDTO(
                    product_id: $item->product_id,
                    warehouse_id: $order->warehouse_id,
                    type: 'stock_out',
                    quantity: (int) ceil($item->quantity),
                    reference: $order->order_number,
                    notes: "Reserved for SO {$order->order_number}",
                ));
            }

            $order->update(['status' => 'confirmed']);

            return $order->fresh(['client', 'creator', 'warehouse', 'items']);
        });
    }

    public function deliver(SalesOrder $order): SalesOrder
    {
        if ($order->status !== 'confirmed') {
            throw ValidationException::withMessages([
                'status' => ['Only confirmed orders can be delivered.'],
            ]);
        }

        $order->update(['status' => 'delivered']);

        return $order->fresh(['client', 'creator', 'warehouse', 'items']);
    }

    public function cancel(SalesOrder $order): SalesOrder
    {
        if (in_array($order->status, ['delivered', 'cancelled'])) {
            throw ValidationException::withMessages([
                'status' => ['This order cannot be cancelled.'],
            ]);
        }

        return DB::transaction(function () use ($order) {
            // Release reserved stock if order was confirmed
            if ($order->status === 'confirmed' && $order->warehouse_id) {
                foreach ($order->items as $item) {
                    if (!$item->product_id) continue;

                    $this->inventoryService->stockIn(new StockMovementDTO(
                        product_id: $item->product_id,
                        warehouse_id: $order->warehouse_id,
                        type: 'stock_in',
                        quantity: (int) ceil($item->quantity),
                        reference: $order->order_number,
                        notes: "Released from cancelled SO {$order->order_number}",
                    ));
                }
            }

            $order->update(['status' => 'cancelled']);

            return $order->fresh(['client', 'creator', 'warehouse', 'items']);
        });
    }

    public function convertToInvoice(SalesOrder $order): \App\Models\Invoice
    {
        if (!in_array($order->status, ['confirmed', 'delivered'])) {
            throw ValidationException::withMessages([
                'status' => ['Only confirmed or delivered orders can be converted to invoices.'],
            ]);
        }

        if ($order->invoice_id) {
            throw ValidationException::withMessages([
                'invoice_id' => ['This order has already been converted to an invoice.'],
            ]);
        }

        $items = $order->items->map(fn ($item) => new InvoiceItemDTO(
            description: $item->description,
            quantity: (float) $item->quantity,
            unit_price: (float) $item->unit_price,
            vat_rate: (float) $item->vat_rate,
            product_id: $item->product_id,
        ))->all();

        $dto = new InvoiceDTO(
            client_id: $order->client_id,
            issue_date: now()->toDateString(),
            due_date: $order->delivery_date?->toDateString() ?? now()->addDays(30)->toDateString(),
            items: $items,
            currency: $order->currency,
            notes: "Converted from SO {$order->order_number}",
        );

        $invoice = $this->invoiceService->create($dto);

        $order->update(['invoice_id' => $invoice->id]);

        return $invoice;
    }

    private function generateOrderNumber(): string
    {
        $companyId = Auth::user()->company_id;
        $prefix = 'SO-' . str_pad($companyId, 4, '0', STR_PAD_LEFT);

        $lastNumber = DB::table('sales_orders')
            ->where('company_id', $companyId)
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('order_number');

        $seq = $lastNumber ? ((int) substr($lastNumber, -6)) + 1 : 1;

        return $prefix . '-' . str_pad($seq, 6, '0', STR_PAD_LEFT);
    }
}
