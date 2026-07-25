<?php

namespace App\Services\WhatsApp\Commands;

use App\Models\Invoice;
use App\Models\WhatsAppConversation;
use App\Services\InvoiceService;
use App\Services\WhatsApp\WhatsAppApiClient;
use App\Services\WhatsAppOrderParserService;

class InvoiceCommands
{
    public function __construct(
        private readonly WhatsAppApiClient $api,
        private readonly InvoiceService $invoiceService,
        private readonly WhatsAppOrderParserService $orderParser,
    ) {}

    public function create(string $phone, array $parsed, WhatsAppConversation $conv): void
    {
        $query = $parsed['query'] ?? '';

        if (empty($query) || $query === ($parsed['params'][0] ?? '')) {
            $conv->setState('awaiting_order_text', 'create_invoice');
            $this->api->sendText($phone,
                "📝 *Create Invoice*\n\nSend me the order details. Example:\n\n_Al Futtaim\n50 USB chargers\n100 HDMI cables_",
                $conv->company_id
            );
            return;
        }

        $result = $this->orderParser->parse($query, $conv->company_id);
        $this->sendOrderConfirmation($phone, $result, $conv);
    }

    public function handleOrderText(string $phone, string $text, WhatsAppConversation $conv): void
    {
        $result = $this->orderParser->parse($text, $conv->company_id);
        $this->sendOrderConfirmation($phone, $result, $conv);
    }

    public function list(string $phone, WhatsAppConversation $conv): void
    {
        $invoices = Invoice::where('company_id', $conv->company_id)
            ->whereNotIn('status', ['cancelled'])
            ->latest()->limit(10)
            ->get(['invoice_number', 'total', 'paid_amount', 'status']);

        if ($invoices->isEmpty()) {
            $this->api->sendText($phone, "📄 No invoices found.", $conv->company_id);
            return;
        }

        $lines = ["📄 *Recent Invoices*\n"];
        foreach ($invoices as $inv) {
            $emoji = match ($inv->status) { 'paid' => '✅', 'overdue' => '🔴', 'sent' => '📤', default => '📝' };
            $lines[] = "{$emoji} {$inv->invoice_number} — {$inv->total} AED ({$inv->status})";
        }

        $this->api->sendText($phone, implode("\n", $lines), $conv->company_id);
    }

    public function sendInvoice(string $phone, string $query, WhatsAppConversation $conv): void
    {
        $invoice = $this->findInvoice($query, $conv->company_id);
        if (!$invoice) { $this->api->sendText($phone, "❌ Invoice not found: {$query}", $conv->company_id); return; }

        $this->invoiceService->sendInvoice($invoice);
        $this->api->sendText($phone, "✅ Invoice {$invoice->invoice_number} sent to {$invoice->client->name}\nTotal: {$invoice->total} AED", $conv->company_id);
    }

    public function pdf(string $phone, string $query, WhatsAppConversation $conv): void
    {
        $invoice = $this->findInvoice($query, $conv->company_id);
        if (!$invoice) { $this->api->sendText($phone, "❌ Invoice not found: {$query}", $conv->company_id); return; }

        $token = \App\Models\ClientPortalToken::generate($conv->company_id, $invoice->id, 1);
        $pdfUrl = url("/api/portal/invoice/{$token->token}/pdf");

        $this->api->sendDocument($phone, $pdfUrl, "invoice-{$invoice->invoice_number}.pdf",
            "📄 Invoice {$invoice->invoice_number} — {$invoice->total} AED", $conv->company_id);
    }

    public function status(string $phone, string $query, WhatsAppConversation $conv): void
    {
        $invoice = $this->findInvoice($query, $conv->company_id);
        if (!$invoice) { $this->api->sendText($phone, "❌ Invoice not found: {$query}", $conv->company_id); return; }

        $emoji = match ($invoice->status) { 'paid' => '✅', 'overdue' => '🔴', 'sent' => '📤', 'draft' => '📝', default => '❓' };
        $msg = "{$emoji} *{$invoice->invoice_number}*\nCustomer: {$invoice->client->name}\nTotal: {$invoice->total} AED\nPaid: {$invoice->paid_amount} AED\nBalance: {$invoice->balance_due} AED\nStatus: {$invoice->status}";

        $buttons = [];
        if ($invoice->status === 'draft') $buttons[] = ['id' => "btn_send_inv_{$invoice->id}", 'title' => '📤 Send'];
        if ($invoice->status !== 'cancelled') $buttons[] = ['id' => "btn_pdf_inv_{$invoice->id}", 'title' => '📄 PDF'];

        $buttons ? $this->api->sendButtons($phone, $msg, $buttons, $conv->company_id) : $this->api->sendText($phone, $msg, $conv->company_id);
    }

    public function sendOrderConfirmation(string $phone, array $result, WhatsAppConversation $conv): void
    {
        $lines = ["📋 *Order Preview*\n", "Customer: " . ($result['customer']['name'] ?? 'Unknown')];
        foreach ($result['items'] ?? [] as $item) {
            $lines[] = "• {$item['quantity']}x {$item['product_name']} @ {$item['unit_price']} AED";
        }
        if (!empty($result['total'])) $lines[] = "\n💰 Total: {$result['total']} AED";

        $conv->setState('awaiting_confirm', 'create_invoice', ['order' => $result]);

        $this->api->sendButtons($phone, implode("\n", $lines), [
            ['id' => 'btn_confirm_inv_0', 'title' => '✅ Confirm'],
            ['id' => 'btn_cancel_inv_0', 'title' => '❌ Cancel'],
        ], $conv->company_id);
    }

    private function findInvoice(string $query, int $companyId): ?Invoice
    {
        return Invoice::where('company_id', $companyId)
            ->where(fn ($q) => $q->where('invoice_number', 'ilike', "%{$query}%")->orWhere('id', is_numeric($query) ? $query : 0))
            ->with('client:id,name')->first();
    }
}
