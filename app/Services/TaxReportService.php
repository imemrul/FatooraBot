<?php

namespace App\Services;

use App\Models\CreditNote;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\PurchaseOrder;

class TaxReportService
{
    public function vatReturn(int $companyId, string $from, string $to): array
    {
        // Output VAT (sales)
        $salesVat = Invoice::where('company_id', $companyId)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->whereBetween('issue_date', [$from, $to])
            ->selectRaw('COALESCE(SUM(subtotal), 0) as taxable_sales, COALESCE(SUM(vat_amount), 0) as output_vat')
            ->first();

        // Input VAT (purchases)
        $purchaseVat = PurchaseOrder::where('company_id', $companyId)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->whereBetween('order_date', [$from, $to])
            ->selectRaw('COALESCE(SUM(subtotal), 0) as taxable_purchases, COALESCE(SUM(vat_amount), 0) as input_vat')
            ->first();

        // Credit notes issued
        $creditVat = CreditNote::where('company_id', $companyId)
            ->whereIn('status', ['issued', 'applied'])
            ->whereBetween('issue_date', [$from, $to])
            ->selectRaw('COALESCE(SUM(subtotal), 0) as credit_amount, COALESCE(SUM(vat_amount), 0) as credit_vat')
            ->first();

        $outputVat = (float) $salesVat->output_vat - (float) $creditVat->credit_vat;
        $inputVat = (float) $purchaseVat->input_vat;
        $netVat = $outputVat - $inputVat;

        return [
            'period' => compact('from', 'to'),
            'sales' => [
                'taxable_amount' => round((float) $salesVat->taxable_sales, 2),
                'vat_amount' => round((float) $salesVat->output_vat, 2),
            ],
            'credit_notes' => [
                'amount' => round((float) $creditVat->credit_amount, 2),
                'vat_adjustment' => round((float) $creditVat->credit_vat, 2),
            ],
            'purchases' => [
                'taxable_amount' => round((float) $purchaseVat->taxable_purchases, 2),
                'vat_amount' => round($inputVat, 2),
            ],
            'summary' => [
                'output_vat' => round($outputVat, 2),
                'input_vat' => round($inputVat, 2),
                'net_vat' => round($netVat, 2),
                'vat_payable' => $netVat > 0 ? round($netVat, 2) : 0,
                'vat_refundable' => $netVat < 0 ? round(abs($netVat), 2) : 0,
            ],
        ];
    }
}
