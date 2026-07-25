<!DOCTYPE html>
<html dir="ltr">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; line-height: 1.5; }
        .container { padding: 40px; }
        .header { display: table; width: 100%; margin-bottom: 30px; }
        .header-left, .header-right { display: table-cell; vertical-align: top; width: 50%; }
        .header-right { text-align: right; }
        .company-name { font-size: 20px; font-weight: bold; color: #4f46e5; }
        .company-name-ar { font-size: 16px; color: #6366f1; direction: rtl; }
        .invoice-title { font-size: 24px; font-weight: bold; color: #1e293b; }
        .invoice-title-ar { font-size: 18px; color: #64748b; direction: rtl; }
        .meta-table { width: 100%; margin-bottom: 25px; }
        .meta-table td { padding: 3px 0; }
        .meta-label { color: #64748b; width: 120px; }
        .meta-label-ar { color: #64748b; text-align: right; direction: rtl; width: 100px; }
        .section-title { font-size: 12px; font-weight: bold; color: #1e293b; margin-bottom: 8px; padding-bottom: 4px; border-bottom: 1px solid #e2e8f0; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th { background: #f8fafc; color: #64748b; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; padding: 8px 10px; text-align: left; border-bottom: 2px solid #e2e8f0; }
        .items-table th.ar { text-align: right; direction: rtl; font-size: 9px; }
        .items-table td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; }
        .items-table .num { text-align: right; }
        .totals-table { width: 280px; margin-left: auto; margin-bottom: 25px; }
        .totals-table td { padding: 4px 10px; }
        .totals-table .label { color: #64748b; }
        .totals-table .label-ar { color: #94a3b8; text-align: right; direction: rtl; font-size: 10px; }
        .totals-table .value { text-align: right; font-weight: bold; }
        .totals-table .grand { font-size: 14px; color: #4f46e5; border-top: 2px solid #4f46e5; padding-top: 8px; }
        .payments-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .payments-table th { background: #f0fdf4; color: #166534; font-size: 10px; padding: 6px 10px; text-align: left; border-bottom: 1px solid #bbf7d0; }
        .payments-table td { padding: 6px 10px; border-bottom: 1px solid #f1f5f9; font-size: 10px; }
        .footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid #e2e8f0; display: table; width: 100%; }
        .footer-left, .footer-right { display: table-cell; vertical-align: top; width: 50%; }
        .footer-right { text-align: right; }
        .qr-box { display: inline-block; border: 1px solid #e2e8f0; padding: 8px; border-radius: 4px; }
        .trn-badge { display: inline-block; background: #f1f5f9; padding: 4px 10px; border-radius: 4px; font-size: 10px; color: #475569; margin-top: 5px; }
        .status-badge { display: inline-block; padding: 3px 12px; border-radius: 12px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .status-draft { background: #f1f5f9; color: #64748b; }
        .status-sent { background: #dbeafe; color: #1d4ed8; }
        .status-paid { background: #dcfce7; color: #166534; }
        .status-overdue { background: #fef2f2; color: #dc2626; }
        .status-cancelled { background: #fef9c3; color: #a16207; }
        .bilingual { display: table; width: 100%; }
        .bilingual .en { display: table-cell; }
        .bilingual .ar { display: table-cell; text-align: right; direction: rtl; color: #94a3b8; font-size: 10px; }
    </style>
</head>
<body>
<div class="container">
    {{-- Header --}}
    <div class="header">
        <div class="header-left">
            <div class="company-name">{{ $company->name }}</div>
            @if($company->address)
                <div style="color: #64748b; margin-top: 4px;">{{ $company->address }}</div>
            @endif
            @if($company->city)
                <div style="color: #64748b;">{{ $company->city }}, {{ $company->country }}</div>
            @endif
            @if($company->phone)
                <div style="color: #64748b;">{{ $company->phone }}</div>
            @endif
            @if($company->tax_registration_number)
                <div class="trn-badge">TRN: {{ $company->tax_registration_number }} | الرقم الضريبي</div>
            @endif
        </div>
        <div class="header-right">
            <div class="invoice-title">TAX INVOICE</div>
            <div class="invoice-title-ar">فاتورة ضريبية</div>
            <div style="margin-top: 8px;">
                <span class="status-badge status-{{ $invoice->status }}">{{ strtoupper($invoice->status) }}</span>
            </div>
        </div>
    </div>

    {{-- Invoice Meta --}}
    <div style="display: table; width: 100%; margin-bottom: 25px;">
        <div style="display: table-cell; width: 50%;">
            <div class="section-title">
                <span>Bill To</span>
                <span style="color: #94a3b8; font-size: 10px; margin-left: 8px;">فاتورة إلى</span>
            </div>
            <div style="font-weight: bold; font-size: 13px;">{{ $client->name }}</div>
            @if($client->contact_person)<div style="color: #64748b;">{{ $client->contact_person }}</div>@endif
            @if($client->address)<div style="color: #64748b;">{{ $client->address }}</div>@endif
            @if($client->city)<div style="color: #64748b;">{{ $client->city }}, {{ $client->country }}</div>@endif
            @if($client->tax_registration_number)
                <div class="trn-badge">TRN: {{ $client->tax_registration_number }}</div>
            @endif
        </div>
        <div style="display: table-cell; width: 50%; text-align: right;">
            <table style="margin-left: auto;">
                <tr><td class="meta-label">Invoice No.</td><td style="font-weight: bold;">{{ $invoice->invoice_number }}</td><td class="meta-label-ar">رقم الفاتورة</td></tr>
                <tr><td class="meta-label">Issue Date</td><td>{{ $invoice->issue_date->format('d/m/Y') }}</td><td class="meta-label-ar">تاريخ الإصدار</td></tr>
                <tr><td class="meta-label">Due Date</td><td>{{ $invoice->due_date->format('d/m/Y') }}</td><td class="meta-label-ar">تاريخ الاستحقاق</td></tr>
                <tr><td class="meta-label">Currency</td><td>{{ $invoice->currency }}</td><td class="meta-label-ar">العملة</td></tr>
            </table>
        </div>
    </div>

    {{-- Line Items --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th>Description <span class="ar">الوصف</span></th>
                <th class="num" style="width: 10%;">Qty <span class="ar">الكمية</span></th>
                <th class="num" style="width: 14%;">Unit Price <span class="ar">سعر الوحدة</span></th>
                <th class="num" style="width: 8%;">VAT% <span class="ar">ض.ق.م</span></th>
                <th class="num" style="width: 12%;">VAT <span class="ar">الضريبة</span></th>
                <th class="num" style="width: 14%;">Total <span class="ar">الإجمالي</span></th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->description }}</td>
                <td class="num">{{ number_format($item->quantity, 2) }}</td>
                <td class="num">{{ number_format($item->unit_price, 2) }}</td>
                <td class="num">{{ number_format($item->vat_rate, 0) }}%</td>
                <td class="num">{{ number_format($item->vat_amount, 2) }}</td>
                <td class="num" style="font-weight: bold;">{{ number_format($item->line_total + $item->vat_amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <table class="totals-table">
        <tr>
            <td class="label">Subtotal</td>
            <td class="label-ar">المجموع الفرعي</td>
            <td class="value">{{ number_format($invoice->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td class="label">VAT (5%)</td>
            <td class="label-ar">ضريبة القيمة المضافة</td>
            <td class="value">{{ number_format($invoice->vat_amount, 2) }}</td>
        </tr>
        @if((float)$invoice->discount > 0)
        <tr>
            <td class="label">Discount</td>
            <td class="label-ar">الخصم</td>
            <td class="value" style="color: #dc2626;">-{{ number_format($invoice->discount, 2) }}</td>
        </tr>
        @endif
        <tr>
            <td class="label grand">Total Due</td>
            <td class="label-ar grand">المبلغ المستحق</td>
            <td class="value grand">{{ $invoice->currency }} {{ number_format($invoice->total, 2) }}</td>
        </tr>
        @if((float)$invoice->paid_amount > 0)
        <tr>
            <td class="label" style="color: #166534;">Paid</td>
            <td class="label-ar" style="color: #166534;">المدفوع</td>
            <td class="value" style="color: #166534;">-{{ number_format($invoice->paid_amount, 2) }}</td>
        </tr>
        <tr>
            <td class="label" style="font-weight: bold;">Balance Due</td>
            <td class="label-ar" style="font-weight: bold;">الرصيد المستحق</td>
            <td class="value" style="font-weight: bold; color: #dc2626;">{{ $invoice->currency }} {{ number_format($invoice->balance_due, 2) }}</td>
        </tr>
        @endif
    </table>

    {{-- Payments --}}
    @if($payments->count())
    <div class="section-title">Payment History <span style="color: #94a3b8; font-size: 10px; margin-left: 8px;">سجل المدفوعات</span></div>
    <table class="payments-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Method</th>
                <th>Reference</th>
                <th style="text-align: right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $payment)
            <tr>
                <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</td>
                <td>{{ $payment->reference ?? '—' }}</td>
                <td style="text-align: right; font-weight: bold;">{{ number_format($payment->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- Notes --}}
    @if($invoice->notes)
    <div style="margin-bottom: 20px;">
        <div class="section-title">Notes <span style="color: #94a3b8; font-size: 10px; margin-left: 8px;">ملاحظات</span></div>
        <div style="color: #64748b; font-size: 10px;">{{ $invoice->notes }}</div>
    </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        <div class="footer-left">
            <div style="color: #94a3b8; font-size: 9px;">
                Generated by FatooraBot<br>
                This is a computer-generated invoice.<br>
                هذه فاتورة صادرة إلكترونياً
            </div>
        </div>
        <div class="footer-right">
            {{-- QR Code placeholder using base64 data --}}
            <div class="qr-box">
                <div style="font-size: 8px; color: #94a3b8; text-align: center; margin-bottom: 4px;">QR Code</div>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode($qrData) }}" width="80" height="80" alt="QR" />
            </div>
        </div>
    </div>
</div>
</body>
</html>
