<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\SalesOrder;

class GlobalSearchService
{
    public function search(string $query, int $limit = 5): array
    {
        $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $query);
        $like = "%{$escaped}%";

        return [
            'invoices' => Invoice::where('invoice_number', 'ilike', $like)
                ->orWhereHas('client', fn ($q) => $q->where('name', 'ilike', $like))
                ->with('client:id,name')
                ->limit($limit)
                ->get(['id', 'client_id', 'invoice_number', 'total', 'status']),

            'clients' => Client::where('name', 'ilike', $like)
                ->orWhere('email', 'ilike', $like)
                ->orWhere('phone', 'ilike', $like)
                ->limit($limit)
                ->get(['id', 'name', 'email', 'phone']),

            'products' => Product::where('name', 'ilike', $like)
                ->orWhere('sku', 'ilike', $like)
                ->orWhere('barcode', 'ilike', $like)
                ->limit($limit)
                ->get(['id', 'name', 'sku', 'unit_price']),

            'quotations' => Quotation::where('quotation_number', 'ilike', $like)
                ->orWhereHas('client', fn ($q) => $q->where('name', 'ilike', $like))
                ->with('client:id,name')
                ->limit($limit)
                ->get(['id', 'client_id', 'quotation_number', 'total', 'status']),

            'sales_orders' => SalesOrder::where('order_number', 'ilike', $like)
                ->orWhereHas('client', fn ($q) => $q->where('name', 'ilike', $like))
                ->with('client:id,name')
                ->limit($limit)
                ->get(['id', 'client_id', 'order_number', 'total', 'status']),
        ];
    }
}
