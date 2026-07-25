<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Product;
use Illuminate\Http\Response;

class ExportService
{
    public function exportInvoices(array $filters = []): Response
    {
        $query = Invoice::with('client:id,name')->latest('issue_date');

        if (!empty($filters['status'])) $query->where('status', $filters['status']);
        if (!empty($filters['from'])) $query->where('issue_date', '>=', $filters['from']);
        if (!empty($filters['to'])) $query->where('issue_date', '<=', $filters['to']);

        $rows = $query->get();

        $headers = ['Invoice #', 'Client', 'Issue Date', 'Due Date', 'Subtotal', 'VAT', 'Total', 'Paid', 'Balance', 'Status', 'Currency'];

        $data = $rows->map(fn ($i) => [
            $i->invoice_number, $i->client->name ?? '', $i->issue_date->toDateString(),
            $i->due_date->toDateString(), $i->subtotal, $i->vat_amount, $i->total,
            $i->paid_amount, $i->balance_due, $i->status, $i->currency,
        ]);

        return $this->toCsv('invoices', $headers, $data);
    }

    public function exportClients(): Response
    {
        $rows = Client::orderBy('name')->get();

        $headers = ['Name', 'Contact Person', 'Email', 'Phone', 'TRN', 'Credit Limit', 'Payment Terms', 'City', 'Country'];

        $data = $rows->map(fn ($c) => [
            $c->name, $c->contact_person, $c->email, $c->phone,
            $c->tax_registration_number, $c->credit_limit, $c->payment_terms,
            $c->city, $c->country,
        ]);

        return $this->toCsv('clients', $headers, $data);
    }

    public function exportProducts(): Response
    {
        $rows = Product::orderBy('name')->get();

        $headers = ['SKU', 'Name', 'Unit Price', 'Cost Price', 'VAT Rate', 'Unit', 'Low Stock Threshold', 'Active'];

        $data = $rows->map(fn ($p) => [
            $p->sku, $p->name, $p->unit_price, $p->cost_price,
            $p->vat_rate, $p->unit, $p->low_stock_threshold, $p->is_active ? 'Yes' : 'No',
        ]);

        return $this->toCsv('products', $headers, $data);
    }

    public function exportPayments(array $filters = []): Response
    {
        $query = InvoicePayment::with(['invoice:id,invoice_number', 'recorder:id,name'])->latest('payment_date');

        if (!empty($filters['from'])) $query->where('payment_date', '>=', $filters['from']);
        if (!empty($filters['to'])) $query->where('payment_date', '<=', $filters['to']);

        $rows = $query->get();

        $headers = ['Invoice #', 'Amount', 'Method', 'Reference', 'Date', 'Recorded By', 'Notes'];

        $data = $rows->map(fn ($p) => [
            $p->invoice->invoice_number ?? '', $p->amount, $p->method,
            $p->reference, $p->payment_date->toDateString(),
            $p->recorder->name ?? '', $p->notes,
        ]);

        return $this->toCsv('payments', $headers, $data);
    }

    public function exportExpenses(array $filters = []): Response
    {
        $query = Expense::with(['category:id,name', 'recorder:id,name'])->latest('expense_date');

        if (!empty($filters['from'])) $query->where('expense_date', '>=', $filters['from']);
        if (!empty($filters['to'])) $query->where('expense_date', '<=', $filters['to']);

        $rows = $query->get();

        $headers = ['Date', 'Category', 'Vendor', 'Amount', 'Currency', 'Reference', 'Description', 'Recorded By'];

        $data = $rows->map(fn ($e) => [
            $e->expense_date->toDateString(), $e->category->name ?? 'Uncategorized',
            $e->vendor, $e->amount, $e->currency, $e->reference,
            $e->description, $e->recorder->name ?? '',
        ]);

        return $this->toCsv('expenses', $headers, $data);
    }

    public function profitLoss(int $companyId, string $from, string $to): array
    {
        $revenue = Invoice::where('company_id', $companyId)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->whereBetween('issue_date', [$from, $to])
            ->sum('total');

        $collected = Invoice::where('company_id', $companyId)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->whereBetween('issue_date', [$from, $to])
            ->sum('paid_amount');

        $expenses = Expense::where('company_id', $companyId)
            ->whereBetween('expense_date', [$from, $to])
            ->sum('amount');

        return [
            'period' => ['from' => $from, 'to' => $to],
            'revenue' => round((float) $revenue, 2),
            'collected' => round((float) $collected, 2),
            'expenses' => round((float) $expenses, 2),
            'gross_profit' => round((float) $revenue - (float) $expenses, 2),
            'net_collected' => round((float) $collected - (float) $expenses, 2),
        ];
    }

    private function toCsv(string $name, array $headers, $data): Response
    {
        $callback = function () use ($headers, $data) {
            $f = fopen('php://output', 'w');
            fputcsv($f, $headers);
            foreach ($data as $row) {
                fputcsv($f, is_array($row) ? $row : $row->toArray());
            }
            fclose($f);
        };

        $filename = $name . '_' . now()->format('Y-m-d') . '.csv';

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
