<?php

namespace App\Services\WhatsApp\Commands;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\WhatsAppConversation;
use App\Services\WhatsApp\WhatsAppApiClient;
use Illuminate\Support\Facades\DB;

class PaymentCommands
{
    public function __construct(private readonly WhatsAppApiClient $api) {}

    public function whoOwes(string $phone, WhatsAppConversation $conv): void
    {
        $clients = Client::where('company_id', $conv->company_id)
            ->withSum(['invoices as outstanding' => fn ($q) => $q->whereNotIn('status', ['draft', 'cancelled', 'paid'])], DB::raw('total - paid_amount'))
            ->get()
            ->filter(fn ($c) => ($c->outstanding ?? 0) > 0)
            ->sortByDesc('outstanding')
            ->take(10);

        if ($clients->isEmpty()) {
            $this->api->sendText($phone, "✅ No outstanding balances!", $conv->company_id);
            return;
        }

        $total = $clients->sum('outstanding');
        $lines = ["💰 *Outstanding: " . number_format($total, 2) . " AED*\n"];

        $i = 1;
        foreach ($clients as $c) {
            $lines[] = "{$i}. {$c->name} — " . number_format($c->outstanding, 2) . " AED";
            $i++;
        }

        $lines[] = "\n_Reply with customer name to send reminder_";

        $this->api->sendText($phone, implode("\n", $lines), $conv->company_id);
    }

    public function sendReminder(string $phone, string $query, WhatsAppConversation $conv): void
    {
        $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $query);
        $client = Client::where('company_id', $conv->company_id)
            ->where('name', 'ilike', "%{$escaped}%")->first();

        if (!$client) {
            $this->api->sendText($phone, "❌ Customer not found: {$query}", $conv->company_id);
            return;
        }

        $overdue = Invoice::where('company_id', $conv->company_id)
            ->where('client_id', $client->id)
            ->whereNotIn('status', ['draft', 'cancelled', 'paid'])
            ->where('due_date', '<', now())
            ->get(['invoice_number', 'total', 'paid_amount', 'due_date']);

        if ($overdue->isEmpty()) {
            $this->api->sendText($phone, "✅ {$client->name} has no overdue invoices.", $conv->company_id);
            return;
        }

        $totalDue = $overdue->sum(fn ($i) => (float) $i->total - (float) $i->paid_amount);
        $lines = ["📨 *Reminder for {$client->name}*\n"];
        foreach ($overdue as $inv) {
            $balance = number_format((float) $inv->total - (float) $inv->paid_amount, 2);
            $lines[] = "• {$inv->invoice_number} — {$balance} AED (due {$inv->due_date->format('d M')})";
        }
        $lines[] = "\nTotal overdue: " . number_format($totalDue, 2) . " AED";

        if ($client->phone) {
            $waLink = "https://wa.me/" . preg_replace('/[^0-9]/', '', $client->phone);
            $lines[] = "\n📱 Send via WhatsApp: {$waLink}";
        }

        $this->api->sendText($phone, implode("\n", $lines), $conv->company_id);
    }
}
