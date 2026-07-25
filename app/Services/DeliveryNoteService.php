<?php

namespace App\Services;

use App\Models\DeliveryNote;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DeliveryNoteService
{
    public function list(?string $status = null): LengthAwarePaginator
    {
        $query = DeliveryNote::with(['client:id,name', 'salesOrder:id,order_number', 'creator:id,name'])->latest();
        if ($status) $query->where('status', $status);
        return $query->paginate(20);
    }

    public function show(DeliveryNote $dn): DeliveryNote
    {
        return $dn->load(['client', 'salesOrder', 'creator:id,name', 'items.product:id,name,sku']);
    }

    public function create(array $data, array $items): DeliveryNote
    {
        return DB::transaction(function () use ($data, $items) {
            $data['created_by'] = Auth::id();
            $data['delivery_number'] = $this->generateNumber();
            $dn = DeliveryNote::create($data);
            foreach ($items as $item) {
                $dn->items()->create($item);
            }
            return $dn->load(['client:id,name', 'items']);
        });
    }

    public function update(DeliveryNote $dn, array $data, array $items): DeliveryNote
    {
        return DB::transaction(function () use ($dn, $data, $items) {
            $dn->update($data);
            $dn->items()->delete();
            foreach ($items as $item) { $dn->items()->create($item); }
            return $dn->fresh()->load(['client:id,name', 'items']);
        });
    }

    public function markInTransit(DeliveryNote $dn): DeliveryNote
    {
        $dn->update(['status' => 'in_transit']);
        return $dn->fresh();
    }

    public function markDelivered(DeliveryNote $dn): DeliveryNote
    {
        $dn->update(['status' => 'delivered', 'delivered_at' => now()]);
        return $dn->fresh();
    }

    public function cancel(DeliveryNote $dn): DeliveryNote
    {
        $dn->update(['status' => 'cancelled']);
        return $dn->fresh();
    }

    public function delete(DeliveryNote $dn): void { $dn->delete(); }

    public function createFromSalesOrder(\App\Models\SalesOrder $so): DeliveryNote
    {
        $items = $so->items->map(fn ($i) => [
            'product_id' => $i->product_id,
            'description' => $i->description,
            'quantity' => $i->quantity,
            'unit' => $i->product?->unit,
        ])->toArray();

        return $this->create([
            'sales_order_id' => $so->id,
            'client_id' => $so->client_id,
            'delivery_date' => $so->delivery_date ?? now()->toDateString(),
            'delivery_address' => $so->client->address,
        ], $items);
    }

    private function generateNumber(): string
    {
        $companyId = Auth::user()->company_id;
        $prefix = 'DN-' . str_pad($companyId, 4, '0', STR_PAD_LEFT);
        $last = DB::table('delivery_notes')->where('company_id', $companyId)->lockForUpdate()->orderByDesc('id')->value('delivery_number');
        $seq = $last ? ((int) substr($last, -6)) + 1 : 1;
        return $prefix . '-' . str_pad($seq, 6, '0', STR_PAD_LEFT);
    }
}
