<?php

namespace App\Services;

use App\Models\ClientPortalToken;
use App\Models\Invoice;

class ClientPortalService
{
    public function generateLink(Invoice $invoice): string
    {
        $token = ClientPortalToken::generate($invoice->company_id, $invoice->id);
        return url("/portal/invoice/{$token->token}");
    }

    public function viewInvoice(string $token): ?array
    {
        $pt = ClientPortalToken::where('token', $token)->first();

        if (!$pt || !$pt->isValid()) return null;

        $pt->update(['last_viewed_at' => now()]);

        $invoice = Invoice::withoutGlobalScopes()
            ->with(['company', 'client', 'items', 'payments'])
            ->find($pt->invoice_id);

        if (!$invoice) return null;

        return [
            'invoice' => $invoice,
            'company' => $invoice->company,
            'client' => $invoice->client,
            'items' => $invoice->items,
            'payments' => $invoice->payments,
        ];
    }

    public function getStatement(string $token): ?array
    {
        $pt = ClientPortalToken::where('token', $token)->first();
        if (!$pt || !$pt->isValid()) return null;

        $invoice = Invoice::withoutGlobalScopes()->find($pt->invoice_id);
        if (!$invoice) return null;

        $invoices = Invoice::withoutGlobalScopes()
            ->where('company_id', $invoice->company_id)
            ->where('client_id', $invoice->client_id)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->orderBy('issue_date')
            ->get(['invoice_number', 'issue_date', 'due_date', 'total', 'paid_amount', 'status']);

        return ['client' => $invoice->client, 'invoices' => $invoices];
    }
}
