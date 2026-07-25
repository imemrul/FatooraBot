<x-mail::message>
# Invoice {{ $invoice->invoice_number }}

Dear {{ $invoice->client->name }},

Please find below the details of your invoice from **{{ $invoice->company->name }}**.

| Detail | Value |
|:-------|------:|
| Invoice # | {{ $invoice->invoice_number }} |
| Issue Date | {{ $invoice->issue_date->format('d M Y') }} |
| Due Date | {{ $invoice->due_date->format('d M Y') }} |
| Subtotal | {{ number_format($invoice->subtotal, 2) }} {{ $invoice->currency }} |
| VAT | {{ number_format($invoice->vat_amount, 2) }} {{ $invoice->currency }} |
| **Total** | **{{ number_format($invoice->total, 2) }} {{ $invoice->currency }}** |

@if($invoice->notes)
**Notes:** {{ $invoice->notes }}
@endif

Please arrange payment by **{{ $invoice->due_date->format('d M Y') }}**.

Thanks,<br>
{{ $invoice->company->name }}
</x-mail::message>
