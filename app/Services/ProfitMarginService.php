<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProfitMarginService
{
    public function productMargins(int $companyId): array
    {
        return Product::where('company_id', $companyId)
            ->whereNotNull('cost_price')
            ->where('cost_price', '>', 0)
            ->select('id', 'name', 'sku', 'unit_price', 'cost_price')
            ->get()
            ->map(function ($p) {
                $margin = (float) $p->unit_price - (float) $p->cost_price;
                $pct = (float) $p->unit_price > 0 ? round($margin / (float) $p->unit_price * 100, 1) : 0;

                // Total sold quantity
                $sold = DB::table('invoice_items')
                    ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                    ->where('invoices.company_id', $p->company_id)
                    ->whereNotIn('invoices.status', ['draft', 'cancelled'])
                    ->where('invoice_items.product_id', $p->id)
                    ->sum('invoice_items.quantity');

                return [
                    'id' => $p->id, 'name' => $p->name, 'sku' => $p->sku,
                    'unit_price' => (float) $p->unit_price, 'cost_price' => (float) $p->cost_price,
                    'margin' => round($margin, 2), 'margin_pct' => $pct,
                    'total_sold' => (float) $sold,
                    'total_profit' => round($margin * $sold, 2),
                ];
            })
            ->sortByDesc('total_profit')
            ->values()
            ->toArray();
    }

    public function invoiceMargins(int $companyId, ?string $from = null, ?string $to = null): array
    {
        $query = Invoice::where('company_id', $companyId)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->with(['client:id,name', 'items.product:id,cost_price']);

        if ($from) $query->where('issue_date', '>=', $from);
        if ($to) $query->where('issue_date', '<=', $to);

        $invoices = $query->latest('issue_date')->limit(50)->get();

        $results = [];
        $totalRevenue = 0;
        $totalCost = 0;

        foreach ($invoices as $inv) {
            $cost = $inv->items->sum(fn ($i) => (float) ($i->product?->cost_price ?? 0) * (float) $i->quantity);
            $revenue = (float) $inv->subtotal;
            $margin = $revenue - $cost;
            $pct = $revenue > 0 ? round($margin / $revenue * 100, 1) : 0;

            $totalRevenue += $revenue;
            $totalCost += $cost;

            $results[] = [
                'invoice_number' => $inv->invoice_number,
                'client' => $inv->client->name ?? '',
                'issue_date' => $inv->issue_date->toDateString(),
                'revenue' => round($revenue, 2),
                'cost' => round($cost, 2),
                'margin' => round($margin, 2),
                'margin_pct' => $pct,
            ];
        }

        return [
            'invoices' => $results,
            'summary' => [
                'total_revenue' => round($totalRevenue, 2),
                'total_cost' => round($totalCost, 2),
                'total_margin' => round($totalRevenue - $totalCost, 2),
                'avg_margin_pct' => $totalRevenue > 0 ? round(($totalRevenue - $totalCost) / $totalRevenue * 100, 1) : 0,
            ],
        ];
    }
}
