<x-mail::message>
# Payment Reminder

Dear {{ $invoice->client->name }},

This is a friendly reminder that invoice **{{ $invoice->invoice_number }}** is now **overdue**.

| Detail | Value |
|:-------|------:|
| Invoice # | {{ $invoice->invoice_number }} |
| Due Date | {{ $invoice->due_date->format('d M Y') }} |
| Days Overdue | {{ now()->diffInDays($invoice->due_date) }} |
| Total | {{ number_format($invoice->total, 2) }} {{ $invoice->currency }} |
| Paid | {{ number_format($invoice->paid_amount, 2) }} {{ $invoice->currency }} |
| **Balance Due** | **{{ number_format($invoice->balance_due, 2) }} {{ $invoice->currency }}** |

Please arrange payment at your earliest convenience.

Thanks,<br>
{{ $invoice->company->name }}
</x-mail::message>
