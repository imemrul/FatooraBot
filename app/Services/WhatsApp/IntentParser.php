<?php

namespace App\Services\WhatsApp;

class IntentParser
{
    private const PATTERNS = [
        // Invoice commands
        'create_invoice' => '/^(invoice|inv|bill|create invoice|new invoice)\b/i',
        'send_invoice' => '/^(send invoice|send inv)\s+(.+)/i',
        'invoice_pdf' => '/^(pdf|get pdf|invoice pdf)\s+(.+)/i',
        'invoice_status' => '/^(invoice status|inv status|check invoice)\s*(.*)/i',
        'list_invoices' => '/^(invoices|my invoices|list invoices|recent invoices)/i',

        // Stock commands
        'stock_check' => '/^(stock|stock check|inventory|stock level)\s*(.*)/i',
        'stock_alerts' => '/^(low stock|stock alerts?|out of stock)/i',

        // Payment commands
        'record_payment' => '/^(pay|payment|record payment|received)\s+(.+)/i',
        'who_owes' => '/^(who owes|outstanding|overdue|receivables|unpaid)/i',
        'send_reminder' => '/^(remind|reminder|send reminder)\s+(.+)/i',

        // Report commands
        'today_summary' => '/^(today|today\'?s?\s*(sales|summary|report)?)/i',
        'weekly_report' => '/^(weekly|this week|week report)/i',
        'monthly_report' => '/^(monthly|this month|month report)/i',
        'aging_report' => '/^(aging|ageing|age report)/i',

        // Quote commands
        'create_quote' => '/^(quote|quotation|new quote|create quote)\b/i',

        // Customer commands
        'customer_info' => '/^(customer|client|who is)\s+(.+)/i',
        'customer_list' => '/^(customers|clients|my customers)/i',

        // Help
        'help' => '/^(help|menu|commands|hi|hello|hey|start)/i',

        // Tutorial
        'learn' => '/^(learn|tutorial|teach|guide|how to)\s*(.*)/i',
    ];

    public function parse(string $message): array
    {
        $message = trim($message);

        // Check for button reply IDs first (e.g., "btn_send_inv_123")
        if (str_starts_with($message, 'btn_')) {
            return $this->parseButtonReply($message);
        }

        foreach (self::PATTERNS as $intent => $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                return [
                    'intent' => $intent,
                    'raw' => $message,
                    'params' => array_slice($matches, 1),
                    'query' => trim($matches[2] ?? $matches[1] ?? ''),
                ];
            }
        }

        return ['intent' => 'unknown', 'raw' => $message, 'params' => [], 'query' => $message];
    }

    private function parseButtonReply(string $id): array
    {
        // btn_send_inv_123, btn_pdf_inv_123, btn_cancel_inv_123
        if (preg_match('/^btn_(send|pdf|cancel|confirm|remind)_(\w+)_(\d+)$/', $id, $m)) {
            return [
                'intent' => "button_{$m[1]}_{$m[2]}",
                'raw' => $id,
                'params' => [$m[1], $m[2], $m[3]],
                'query' => $m[3],
                'entity_id' => (int) $m[3],
            ];
        }

        return ['intent' => 'unknown', 'raw' => $id, 'params' => [], 'query' => ''];
    }
}
