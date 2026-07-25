<?php

namespace App\Console\Commands;

use App\Services\PaymentReminderService;
use Illuminate\Console\Command;

class ProcessOverdueInvoices extends Command
{
    protected $signature = 'invoices:process-overdue';
    protected $description = 'Detect overdue invoices, send reminders, and update statuses';

    public function handle(PaymentReminderService $service): int
    {
        $this->info('Processing overdue invoices...');

        $sent = $service->processAllOverdue();

        $this->info("Done. {$sent} reminder(s) sent.");

        return self::SUCCESS;
    }
}
