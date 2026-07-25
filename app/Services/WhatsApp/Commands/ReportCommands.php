<?php

namespace App\Services\WhatsApp\Commands;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\WhatsAppConversation;
use App\Services\AgingReportService;
use App\Services\WhatsApp\WhatsAppApiClient;

class ReportCommands
{
    public function __construct(
        private readonly WhatsAppApiClient $api,
        private readonly AgingReportService $agingService,
    ) {}

    public function today(string $phone, WhatsAppConversation $conv): void
    {
        $cid = $conv->company_id;
        $today = now()->toDateString();

        $newInvoices = Invoice::where('company_id', $cid)->whereDate('issue_date', $today)->count();
        $todaySales = Invoice::where('company_id', $cid)->whereDate('issue_date', $today)->whereNotIn('status', ['draft', 'cancelled'])->sum('total');
        $todayCollected = Invoice::where('company_id', $cid)->whereHas('payments', fn ($q) => $q->whereDate('payment_date', $today))->sum('paid_amount');
        $overdue = Invoice::where('company_id', $cid)->where('status', 'overdue')->count();

        $msg = "📊 *Today's Summary*\n"
            . "━━━━━━━━━━━━━━━\n"
            . "📄 New invoices: {$newInvoices}\n"
            . "💰 Sales: " . number_format((float) $todaySales, 2) . " AED\n"
            . "✅ Collected: " . number_format((float) $todayCollected, 2) . " AED\n"
            . "🔴 Overdue: {$overdue} invoices";

        $this->api->sendText($phone, $msg, $cid);
    }

    public function monthly(string $phone, WhatsAppConversation $conv): void
    {
        $cid = $conv->company_id;
        $start = now()->startOfMonth()->toDateString();
        $end = now()->toDateString();

        $revenue = Invoice::where('company_id', $cid)->whereNotIn('status', ['draft', 'cancelled'])->whereBetween('issue_date', [$start, $end])->sum('total');
        $collected = Invoice::where('company_id', $cid)->whereNotIn('status', ['draft', 'cancelled'])->whereBetween('issue_date', [$start, $end])->sum('paid_amount');
        $expenses = Expense::where('company_id', $cid)->whereBetween('expense_date', [$start, $end])->sum('amount');
        $invoiceCount = Invoice::where('company_id', $cid)->whereBetween('issue_date', [$start, $end])->count();

        $profit = (float) $revenue - (float) $expenses;

        $msg = "📈 *Monthly Report (" . now()->format('M Y') . ")*\n"
            . "━━━━━━━━━━━━━━━\n"
            . "📄 Invoices: {$invoiceCount}\n"
            . "💰 Revenue: " . number_format((float) $revenue, 2) . " AED\n"
            . "✅ Collected: " . number_format((float) $collected, 2) . " AED\n"
            . "📉 Expenses: " . number_format((float) $expenses, 2) . " AED\n"
            . "━━━━━━━━━━━━━━━\n"
            . ($profit >= 0 ? "✅" : "❌") . " Profit: " . number_format($profit, 2) . " AED";

        $this->api->sendText($phone, $msg, $cid);
    }

    public function aging(string $phone, WhatsAppConversation $conv): void
    {
        $data = $this->agingService->getAgingSummary($conv->company_id);
        $s = $data['summary'];

        $msg = "📊 *Aging Report*\n"
            . "━━━━━━━━━━━━━━━\n"
            . "🟢 Current: " . number_format($s['current'], 2) . "\n"
            . "🟡 1-30 days: " . number_format($s['1_30'], 2) . "\n"
            . "🟠 31-60 days: " . number_format($s['31_60'], 2) . "\n"
            . "🔴 61-90 days: " . number_format($s['61_90'], 2) . "\n"
            . "⛔ 90+ days: " . number_format($s['91_120'] + $s['over_120'], 2) . "\n"
            . "━━━━━━━━━━━━━━━\n"
            . "💰 Total: " . number_format($data['total_outstanding'], 2) . " AED";

        if (!empty($data['by_client'])) {
            $msg .= "\n\n*Top Debtors:*";
            foreach (array_slice($data['by_client'], 0, 5) as $c) {
                $msg .= "\n• {$c['client']} — " . number_format($c['total'], 2);
            }
        }

        $this->api->sendText($phone, $msg, $conv->company_id);
    }
}
