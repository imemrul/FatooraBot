<?php

namespace App\Listeners;

use App\Events\InvoiceCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class LogInvoiceCreated implements ShouldQueue
{
    public function handle(InvoiceCreated $event): void
    {
        Log::info('Invoice created', [
            'invoice_id' => $event->invoice->id,
            'invoice_number' => $event->invoice->invoice_number,
            'company_id' => $event->invoice->company_id,
            'total' => $event->invoice->total,
        ]);
    }
}
