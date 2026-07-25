<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class AgingReportService
{
    public function getAgingSummary(int $companyId): array
    {
        $invoices = Invoice::where('company_id', $companyId)
            ->whereNotIn('status', ['draft', 'cancelled', 'paid'])
            ->where('total', '>', DB::raw('paid_amount'))
            ->with('client:id,name')
            ->get(['id', 'client_id', 'invoice_number', 'due_date', 'total', 'paid_amount']);

        $buckets = ['current' => 0, '1_30' => 0, '31_60' => 0, '61_90' => 0, '91_120' => 0, 'over_120' => 0];
        $byClient = [];

        foreach ($invoices as $inv) {
            $balance = (float) $inv->total - (float) $inv->paid_amount;
            $daysOverdue = max(0, now()->diffInDays($inv->due_date, false) * -1);

            $bucket = match (true) {
                $daysOverdue <= 0 => 'current',
                $daysOverdue <= 30 => '1_30',
                $daysOverdue <= 60 => '31_60',
                $daysOverdue <= 90 => '61_90',
                $daysOverdue <= 120 => '91_120',
                default => 'over_120',
            };

            $buckets[$bucket] += $balance;

            $clientName = $inv->client->name ?? 'Unknown';
            if (!isset($byClient[$clientName])) {
                $byClient[$clientName] = ['client' => $clientName, 'current' => 0, '1_30' => 0, '31_60' => 0, '61_90' => 0, '91_120' => 0, 'over_120' => 0, 'total' => 0];
            }
            $byClient[$clientName][$bucket] += $balance;
            $byClient[$clientName]['total'] += $balance;
        }

        // Sort by total descending
        usort($byClient, fn ($a, $b) => $b['total'] <=> $a['total']);

        return [
            'summary' => array_map(fn ($v) => round($v, 2), $buckets),
            'total_outstanding' => round(array_sum($buckets), 2),
            'by_client' => array_slice($byClient, 0, 20),
            'invoice_count' => $invoices->count(),
        ];
    }
}
