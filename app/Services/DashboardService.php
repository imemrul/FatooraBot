<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function __construct(
        private readonly PaymentReminderService $reminderService,
        private readonly InventoryService $inventoryService,
    ) {}

    public function getData(int $companyId): array
    {
        return [
            'stats' => $this->getStats($companyId),
            'revenue_trend' => $this->getRevenueTrend($companyId),
            'collection_trend' => $this->getCollectionTrend($companyId),
            'top_customers' => $this->getTopCustomers($companyId),
            'low_stock' => $this->getLowStock($companyId),
            'reminders' => $this->reminderService->getDashboardData($companyId),
        ];
    }

    private function getStats(int $companyId): array
    {
        $today = Carbon::today();

        // Combine into single query with conditional aggregation
        $invoiceStats = Invoice::where('company_id', $companyId)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->selectRaw("
                COALESCE(SUM(CASE WHEN issue_date = ? THEN total ELSE 0 END), 0) as daily_sales,
                COALESCE(SUM(CASE WHEN EXTRACT(YEAR FROM issue_date) = ? AND EXTRACT(MONTH FROM issue_date) = ? THEN total ELSE 0 END), 0) as monthly_revenue,
                COALESCE(SUM(CASE WHEN status NOT IN ('paid') THEN total - paid_amount ELSE 0 END), 0) as total_outstanding,
                COUNT(*) as invoice_count
            ", [$today, $today->year, $today->month])
            ->first();

        $monthlyCollected = InvoicePayment::where('company_id', $companyId)
            ->whereYear('payment_date', $today->year)
            ->whereMonth('payment_date', $today->month)
            ->sum('amount');

        $clientCount = Client::where('company_id', $companyId)->count();

        return [
            'daily_sales' => round((float) $invoiceStats->daily_sales, 2),
            'monthly_revenue' => round((float) $invoiceStats->monthly_revenue, 2),
            'monthly_collected' => round((float) $monthlyCollected, 2),
            'total_outstanding' => round((float) $invoiceStats->total_outstanding, 2),
            'invoice_count' => $invoiceStats->invoice_count,
            'client_count' => $clientCount,
        ];
    }

    private function getRevenueTrend(int $companyId): array
    {
        $start = Carbon::today()->subMonths(11)->startOfMonth();

        $data = Invoice::where('company_id', $companyId)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->where('issue_date', '>=', $start)
            ->selectRaw("TO_CHAR(issue_date, 'YYYY-MM') as month, SUM(total) as revenue")
            ->groupByRaw("TO_CHAR(issue_date, 'YYYY-MM')")
            ->pluck('revenue', 'month');

        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = Carbon::today()->subMonths($i)->startOfMonth();
            $key = $m->format('Y-m');
            $months[] = ['month' => $key, 'label' => $m->format('M'), 'revenue' => round((float) ($data[$key] ?? 0), 2)];
        }

        return $months;
    }

    private function getCollectionTrend(int $companyId): array
    {
        $start = Carbon::today()->subMonths(11)->startOfMonth();

        $data = InvoicePayment::where('company_id', $companyId)
            ->where('payment_date', '>=', $start)
            ->selectRaw("TO_CHAR(payment_date, 'YYYY-MM') as month, SUM(amount) as collected")
            ->groupByRaw("TO_CHAR(payment_date, 'YYYY-MM')")
            ->pluck('collected', 'month');

        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = Carbon::today()->subMonths($i)->startOfMonth();
            $key = $m->format('Y-m');
            $months[] = ['month' => $key, 'label' => $m->format('M'), 'collected' => round((float) ($data[$key] ?? 0), 2)];
        }

        return $months;
    }

    private function getTopCustomers(int $companyId, int $limit = 5): array
    {
        return Client::where('company_id', $companyId)
            ->whereHas('invoices', fn ($q) => $q->whereNotIn('status', ['draft', 'cancelled']))
            ->withSum(['invoices as total_invoiced' => fn ($q) => $q->whereNotIn('status', ['draft', 'cancelled'])], 'total')
            ->withSum(['invoices as total_paid' => fn ($q) => $q->whereNotIn('status', ['draft', 'cancelled'])], 'paid_amount')
            ->orderByDesc('total_invoiced')
            ->take($limit)
            ->get()
            ->map(fn (Client $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'total_invoiced' => round((float) $c->total_invoiced, 2),
                'total_paid' => round((float) $c->total_paid, 2),
                'outstanding' => round((float) $c->total_invoiced - (float) $c->total_paid, 2),
            ])
            ->all();
    }

    private function getLowStock(int $companyId, int $limit = 10): array
    {
        // Database-level aggregation — no loading all products into memory
        $lowStock = $this->inventoryService->getLowStockProducts();
        $outOfStock = $this->inventoryService->getOutOfStockProducts();

        $combined = collect();

        foreach ($outOfStock as $p) {
            $combined->push(['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'total_stock' => 0, 'threshold' => $p->low_stock_threshold, 'status' => 'out']);
        }

        foreach ($lowStock as $p) {
            $combined->push(['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'total_stock' => $p->total_stock, 'threshold' => $p->low_stock_threshold, 'status' => 'low']);
        }

        return $combined->sortBy('total_stock')->take($limit)->values()->all();
    }
}
