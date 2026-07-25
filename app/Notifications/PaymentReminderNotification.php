<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Invoice $invoice,
        private readonly string $companyName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $daysOverdue = now()->diffInDays($this->invoice->due_date);

        return (new MailMessage)
            ->subject("Payment Reminder — Invoice {$this->invoice->invoice_number}")
            ->greeting("Dear {$this->invoice->client->name},")
            ->line("This is a friendly reminder that invoice **{$this->invoice->invoice_number}** has an outstanding balance.")
            ->line("**Invoice Date:** {$this->invoice->issue_date->format('d M Y')}")
            ->line("**Due Date:** {$this->invoice->due_date->format('d M Y')}")
            ->line("**Total Amount:** {$this->invoice->currency} " . number_format($this->invoice->total, 2))
            ->line("**Amount Paid:** {$this->invoice->currency} " . number_format($this->invoice->paid_amount, 2))
            ->line("**Balance Due:** {$this->invoice->currency} " . number_format($this->invoice->balance_due, 2))
            ->when($daysOverdue > 0, fn ($m) => $m->line("This invoice is **{$daysOverdue} days overdue**."))
            ->line('Please arrange payment at your earliest convenience.')
            ->salutation("Regards,\n{$this->companyName}");
    }
}
