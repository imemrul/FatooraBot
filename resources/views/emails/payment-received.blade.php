<x-mail::message>
# Payment Received

Dear {{ $invoice->client->name }},

We have received your payment for invoice **{{ $invoice->invoice_number }}**.

| Detail | Value |
|:-------|------:|
| Payment Amount | {{ number_format($payment->amount, 2) }} {{ $invoice->currency }} |
| Payment Method | {{ ucfirst($payment->method) }} |
| Payment Date | {{ $payment->payment_date->format('d M Y') }} |
| Total Invoice | {{ number_format($invoice->total, 2) }} {{ $invoice->currency }} |
| Total Paid | {{ number_format($invoice->paid_amount, 2) }} {{ $invoice->currency }} |
| Balance Due | {{ number_format($invoice->balance_due, 2) }} {{ $invoice->currency }} |

@if($invoice->balance_due <= 0)
This invoice is now **fully paid**. Thank you!
@else
Remaining balance: **{{ number_format($invoice->balance_due, 2) }} {{ $invoice->currency }}**
@endif

Thanks,<br>
{{ $invoice->company->name }}
</x-mail::message>
