<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppPhone;
use App\Services\WhatsApp\Commands\InvoiceCommands;
use App\Services\WhatsApp\Commands\PaymentCommands;
use App\Services\WhatsApp\Commands\ReportCommands;
use App\Services\WhatsApp\Commands\StockCommands;

class CommandRouter
{
    public function __construct(
        private readonly WhatsAppApiClient $api,
        private readonly IntentParser $parser,
        private readonly InvoiceCommands $invoiceCmd,
        private readonly StockCommands $stockCmd,
        private readonly PaymentCommands $paymentCmd,
        private readonly ReportCommands $reportCmd,
    ) {}

    public function handle(string $phone, string $message, ?string $buttonId = null): void
    {
        $phone = WhatsAppPhone::normalize($phone);

        // Log inbound
        $linked = WhatsAppPhone::findByPhone($phone);
        WhatsAppMessage::create([
            'company_id' => $linked?->company_id,
            'phone' => $phone,
            'direction' => 'inbound',
            'body' => $buttonId ?? $message,
            'message_type' => $buttonId ? 'interactive' : 'text',
        ]);

        // Identify tenant
        if (!$linked) {
            $this->api->sendText($phone, "👋 Welcome to *FatooraBot*!\n\nYour phone is not linked to any account. Please link it from the app:\n\n*Settings → WhatsApp → Link Phone*");
            return;
        }

        $conv = WhatsAppConversation::getOrCreate($phone, $linked->company_id, $linked->user_id);

        // Reset expired conversations
        if ($conv->isExpired()) $conv->reset();

        // Handle button replies
        if ($buttonId) {
            $this->handleButton($phone, $buttonId, $conv);
            return;
        }

        // Handle multi-step flow
        if ($conv->state !== 'idle') {
            $this->handleConversationState($phone, $message, $conv);
            return;
        }

        // Parse intent
        $parsed = $this->parser->parse($message);
        $this->dispatch($phone, $parsed, $conv);
    }

    private function dispatch(string $phone, array $parsed, WhatsAppConversation $conv): void
    {
        match ($parsed['intent']) {
            'help' => $this->sendHelp($phone, $conv),

            'create_invoice' => $this->invoiceCmd->create($phone, $parsed, $conv),
            'list_invoices' => $this->invoiceCmd->list($phone, $conv),
            'send_invoice' => $this->invoiceCmd->sendInvoice($phone, $parsed['query'], $conv),
            'invoice_pdf' => $this->invoiceCmd->pdf($phone, $parsed['query'], $conv),
            'invoice_status' => $this->invoiceCmd->status($phone, $parsed['query'], $conv),

            'stock_check' => $this->stockCmd->check($phone, $parsed['query'], $conv),
            'stock_alerts' => $this->stockCmd->alerts($phone, $conv),

            'who_owes' => $this->paymentCmd->whoOwes($phone, $conv),
            'send_reminder' => $this->paymentCmd->sendReminder($phone, $parsed['query'], $conv),

            'today_summary' => $this->reportCmd->today($phone, $conv),
            'weekly_report', 'monthly_report' => $this->reportCmd->monthly($phone, $conv),
            'aging_report' => $this->reportCmd->aging($phone, $conv),

            'learn' => $this->sendLearnMenu($phone, $parsed['query'], $conv),

            default => $this->api->sendText($phone, "🤔 I didn't understand that.\n\nType *help* to see available commands.", $conv->company_id),
        };
    }

    private function handleButton(string $phone, string $buttonId, WhatsAppConversation $conv): void
    {
        $parsed = $this->parser->parse($buttonId);

        match (true) {
            $parsed['intent'] === 'button_confirm_inv' => $this->confirmInvoice($phone, $conv),
            $parsed['intent'] === 'button_cancel_inv' => $this->cancelAction($phone, $conv),
            $parsed['intent'] === 'button_send_inv' => $this->invoiceCmd->sendInvoice($phone, $parsed['query'], $conv),
            $parsed['intent'] === 'button_pdf_inv' => $this->invoiceCmd->pdf($phone, $parsed['query'], $conv),
            default => $this->api->sendText($phone, "❓ Unknown action.", $conv->company_id),
        };
    }

    private function handleConversationState(string $phone, string $message, WhatsAppConversation $conv): void
    {
        if (strtolower(trim($message)) === 'cancel') {
            $conv->reset();
            $this->api->sendText($phone, "❌ Cancelled.", $conv->company_id);
            return;
        }

        match ($conv->state) {
            'awaiting_order_text' => $this->invoiceCmd->handleOrderText($phone, $message, $conv),
            default => $conv->reset(),
        };
    }

    private function confirmInvoice(string $phone, WhatsAppConversation $conv): void
    {
        $order = $conv->context['order'] ?? null;
        if (!$order) {
            $this->api->sendText($phone, "❌ Session expired. Start again with *invoice*", $conv->company_id);
            $conv->reset();
            return;
        }

        try {
            // Use the order parser confirm flow
            $parser = app(WhatsAppOrderParserService::class);
            $invoice = $parser->confirm($order, $conv->company_id, $conv->user_id);

            $this->api->sendButtons($phone,
                "✅ *Invoice Created!*\n\n{$invoice->invoice_number}\nCustomer: {$invoice->client->name}\nTotal: {$invoice->total} AED",
                [
                    ['id' => "btn_send_inv_{$invoice->id}", 'title' => '📤 Send'],
                    ['id' => "btn_pdf_inv_{$invoice->id}", 'title' => '📄 PDF'],
                ],
                $conv->company_id
            );
        } catch (\Throwable $e) {
            $this->api->sendText($phone, "❌ Error: {$e->getMessage()}", $conv->company_id);
        }

        $conv->reset();
    }

    private function cancelAction(string $phone, WhatsAppConversation $conv): void
    {
        $conv->reset();
        $this->api->sendText($phone, "❌ Cancelled.", $conv->company_id);
    }

    private function sendHelp(string $phone, WhatsAppConversation $conv): void
    {
        $this->api->sendText($phone,
            "🤖 *FatooraBot Commands*\n"
            . "━━━━━━━━━━━━━━━\n\n"
            . "📄 *Invoices*\n"
            . "• `invoice` — Create new invoice\n"
            . "• `invoices` — List recent\n"
            . "• `send invoice INV-xxx` — Send to customer\n"
            . "• `pdf INV-xxx` — Get PDF\n\n"
            . "📦 *Stock*\n"
            . "• `stock USB` — Check product stock\n"
            . "• `low stock` — View alerts\n\n"
            . "💰 *Payments*\n"
            . "• `who owes` — Outstanding balances\n"
            . "• `remind Al Futtaim` — Send reminder\n\n"
            . "📊 *Reports*\n"
            . "• `today` — Today's summary\n"
            . "• `monthly` — Month report\n"
            . "• `aging` — Aging report\n\n"
            . "📚 *Learn*\n"
            . "• `learn` — Interactive tutorials\n\n"
            . "_Type any command to get started!_",
            $conv->company_id
        );
    }

    private function sendLearnMenu(string $phone, string $topic, WhatsAppConversation $conv): void
    {
        $tutorials = \App\Services\TutorialService::definitions();

        if (!empty($topic) && !in_array(strtolower($topic), ['learn', 'tutorial', 'teach', 'guide', 'how to'])) {
            // Search for specific topic
            $found = null;
            foreach ($tutorials as $key => $t) {
                if (str_contains(strtolower($t['title']), strtolower($topic))) {
                    $found = ['key' => $key, ...$t];
                    break;
                }
            }

            if ($found) {
                $lines = ["{$found['icon']} *{$found['title']}*\n"];
                foreach ($found['steps'] as $i => $step) {
                    $num = $i + 1;
                    $lines[] = "*Step {$num}:* {$step['title']}\n{$step['content']}\n";
                }
                $this->api->sendText($phone, implode("\n", $lines), $conv->company_id);
                return;
            }
        }

        // Show tutorial menu
        $lines = ["📚 *FatooraBot Tutorials*\n", "Choose a topic to learn:\n"];
        $i = 1;
        foreach ($tutorials as $key => $t) {
            $lines[] = "{$i}. {$t['icon']} *{$t['title']}*";
            $lines[] = "   {$t['description']}";
            $lines[] = "   _Type: learn {$t['title']}_\n";
            $i++;
        }
        $lines[] = "\n💡 _Example: `learn invoice` or `learn whatsapp`_";

        $this->api->sendText($phone, implode("\n", $lines), $conv->company_id);
    }
}
