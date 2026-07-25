<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\PaymentReminder;
use App\Notifications\PaymentReminderNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class PaymentReminderService
{
    public function getOverdueInvoices(?int $companyId = null): Collection
    {
        return Invoice::with(['client', 'company'])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotIn('status', ['draft', 'cancelled', 'paid'])
            ->where('due_date', '<', now()->startOfDay())
            ->whereRaw('total > paid_amount')
            ->orderBy('due_date')
            ->get();
    }

    public function getDueTodayInvoices(?int $companyId = null): Collection
    {
        return Invoice::with(['client', 'company'])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotIn('status', ['draft', 'cancelled', 'paid'])
            ->whereDate('due_date', now()->toDateString())
            ->whereRaw('total > paid_amount')
            ->get();
    }

    public function getDueSoonInvoices(?int $companyId = null, int $days = 7): Collection
    {
        return Invoice::with(['client', 'company'])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotIn('status', ['draft', 'cancelled', 'paid'])
            ->whereBetween('due_date', [now()->addDay(), now()->addDays($days)])
            ->whereRaw('total > paid_amount')
            ->orderBy('due_date')
            ->get();
    }

    public function getDashboardData(int $companyId): array
    {
        $overdue = $this->getOverdueInvoices($companyId);
        $dueToday = $this->getDueTodayInvoices($companyId);
        $dueSoon = $this->getDueSoonInvoices($companyId);

        return [
            'overdue' => [
                'count' => $overdue->count(),
                'total' => $overdue->sum(fn ($i) => $i->balance_due),
                'invoices' => $overdue->take(10)->map(fn ($i) => $this->formatInvoice($i)),
            ],
            'due_today' => [
                'count' => $dueToday->count(),
                'total' => $dueToday->sum(fn ($i) => $i->balance_due),
                'invoices' => $dueToday->map(fn ($i) => $this->formatInvoice($i)),
            ],
            'due_soon' => [
                'count' => $dueSoon->count(),
                'total' => $dueSoon->sum(fn ($i) => $i->balance_due),
                'invoices' => $dueSoon->take(10)->map(fn ($i) => $this->formatInvoice($i)),
            ],
            'recent_reminders' => PaymentReminder::where('company_id', $companyId)
                ->with(['invoice.client'])
                ->latest('sent_at')
                ->take(10)
                ->get()
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'invoice_number' => $r->invoice->invoice_number,
                    'client_name' => $r->invoice->client->name,
                    'channel' => $r->channel,
                    'status' => $r->status,
                    'sent_at' => $r->sent_at->toDateTimeString(),
                ]),
        ];
    }

    public function sendEmailReminder(Invoice $invoice, ?int $sentBy = null): PaymentReminder
    {
        $invoice->load(['client', 'company']);
        $client = $invoice->client;
        $email = $client->email;

        $status = 'sent';

        try {
            if ($email) {
                Notification::route('mail', $email)
                    ->notify(new PaymentReminderNotification($invoice, $invoice->company->name));
            } else {
                $status = 'failed';
            }
        } catch (\Exception $e) {
            Log::error('Payment reminder email failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            $status = 'failed';
        }

        return PaymentReminder::create([
            'company_id' => $invoice->company_id,
            'invoice_id' => $invoice->id,
            'sent_by' => $sentBy ?? Auth::id(),
            'channel' => 'email',
            'recipient' => $email ?? 'no-email',
            'status' => $status,
            'message_preview' => "Payment reminder for {$invoice->invoice_number} — Balance: {$invoice->currency} " . number_format($invoice->balance_due, 2),
            'sent_at' => now(),
        ]);
    }

    public function generateWhatsAppMessage(Invoice $invoice): string
    {
        $invoice->load(['client', 'company']);
        $daysOverdue = max(0, now()->diffInDays($invoice->due_date, false) * -1);

        $lines = [
            "Dear {$invoice->client->name},",
            "",
            "This is a payment reminder from {$invoice->company->name}.",
            "",
            "Invoice: {$invoice->invoice_number}",
            "Date: {$invoice->issue_date->format('d/m/Y')}",
            "Due: {$invoice->due_date->format('d/m/Y')}",
            "Total: {$invoice->currency} " . number_format($invoice->total, 2),
            "Paid: {$invoice->currency} " . number_format($invoice->paid_amount, 2),
            "*Balance Due: {$invoice->currency} " . number_format($invoice->balance_due, 2) . "*",
        ];

        if ($daysOverdue > 0) {
            $lines[] = "";
            $lines[] = "⚠️ This invoice is {$daysOverdue} days overdue.";
        }

        $lines[] = "";
        $lines[] = "Please arrange payment at your earliest convenience.";
        $lines[] = "Thank you for your business.";

        return implode("\n", $lines);
    }

    public function logWhatsAppReminder(Invoice $invoice, ?int $sentBy = null): PaymentReminder
    {
        $message = $this->generateWhatsAppMessage($invoice);

        return PaymentReminder::create([
            'company_id' => $invoice->company_id,
            'invoice_id' => $invoice->id,
            'sent_by' => $sentBy ?? Auth::id(),
            'channel' => 'whatsapp',
            'recipient' => $invoice->client->phone ?? 'no-phone',
            'status' => 'sent',
            'message_preview' => mb_substr($message, 0, 500),
            'sent_at' => now(),
        ]);
    }

    public function processAllOverdue(): int
    {
        $sent = 0;

        // Chunk by company to avoid loading all invoices into memory
        Invoice::withoutGlobalScopes()
            ->with(['client', 'company'])
            ->whereNotIn('status', ['draft', 'cancelled', 'paid'])
            ->where('due_date', '<', now()->startOfDay())
            ->whereRaw('total > paid_amount')
            ->chunk(100, function ($invoices) use (&$sent) {
                $invoiceIds = $invoices->pluck('id');
                $lastReminders = PaymentReminder::whereIn('invoice_id', $invoiceIds)
                    ->select('invoice_id')
                    ->selectRaw('MAX(sent_at) as last_sent')
                    ->groupBy('invoice_id')
                    ->pluck('last_sent', 'invoice_id');

                foreach ($invoices as $invoice) {
                    $lastSent = $lastReminders[$invoice->id] ?? null;
                    if ($lastSent && now()->diffInDays($lastSent) < 3) {
                        continue;
                    }

                    if ($invoice->client?->email) {
                        $this->sendEmailReminder($invoice, null);
                        $sent++;
                    }

                    if ($invoice->status === 'sent') {
                        $invoice->update(['status' => 'overdue']);
                    }
                }
            });

        return $sent;
    }

    private function formatInvoice(Invoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'client_name' => $invoice->client->name,
            'client_phone' => $invoice->client->phone,
            'client_email' => $invoice->client->email,
            'due_date' => $invoice->due_date->toDateString(),
            'days_overdue' => max(0, (int) now()->diffInDays($invoice->due_date, false) * -1),
            'total' => (float) $invoice->total,
            'paid_amount' => (float) $invoice->paid_amount,
            'balance_due' => $invoice->balance_due,
            'currency' => $invoice->currency,
            'status' => $invoice->status,
        ];
    }
}
