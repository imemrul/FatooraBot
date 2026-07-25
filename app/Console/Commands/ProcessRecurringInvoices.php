<?php

namespace App\Console\Commands;

use App\Services\RecurringInvoiceService;
use Illuminate\Console\Command;

class ProcessRecurringInvoices extends Command
{
    protected $signature = 'invoices:process-recurring';
    protected $description = 'Generate invoices from active recurring templates that are due';

    public function handle(RecurringInvoiceService $service): int
    {
        $count = $service->processAllDue();
        $this->info("Generated {$count} invoice(s) from recurring templates.");
        return self::SUCCESS;
    }
}
