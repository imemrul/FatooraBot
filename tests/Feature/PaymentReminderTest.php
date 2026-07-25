<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\PaymentReminder;
use App\Models\User;
use App\Services\PaymentReminderService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PaymentReminderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Company $company;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->user->assignRole('owner');
        $this->client = Client::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'client@example.com',
            'phone' => '+971501234567',
        ]);
    }

    private function act()
    {
        return $this->actingAs($this->user, 'sanctum');
    }

    private function createInvoice(string $status, string $dueDate, float $total = 1000, float $paid = 0): Invoice
    {
        return Invoice::create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
            'invoice_number' => 'INV-' . fake()->unique()->numerify('######'),
            'issue_date' => now()->subDays(30),
            'due_date' => $dueDate,
            'total' => $total,
            'paid_amount' => $paid,
            'status' => $status,
        ]);
    }

    // ── Detection ──

    public function test_detects_overdue_invoices(): void
    {
        $this->createInvoice('sent', now()->subDays(5)->toDateString());
        $this->createInvoice('sent', now()->addDays(5)->toDateString()); // not overdue
        $this->createInvoice('paid', now()->subDays(5)->toDateString()); // paid

        $service = app(PaymentReminderService::class);
        $overdue = $service->getOverdueInvoices($this->company->id);

        $this->assertCount(1, $overdue);
    }

    public function test_detects_due_today_invoices(): void
    {
        $this->createInvoice('sent', now()->toDateString());
        $this->createInvoice('sent', now()->subDay()->toDateString()); // overdue, not today

        $service = app(PaymentReminderService::class);
        $dueToday = $service->getDueTodayInvoices($this->company->id);

        $this->assertCount(1, $dueToday);
    }

    public function test_detects_due_soon_invoices(): void
    {
        $this->createInvoice('sent', now()->addDays(3)->toDateString());
        $this->createInvoice('sent', now()->addDays(10)->toDateString()); // too far

        $service = app(PaymentReminderService::class);
        $dueSoon = $service->getDueSoonInvoices($this->company->id, 7);

        $this->assertCount(1, $dueSoon);
    }

    public function test_excludes_fully_paid_from_overdue(): void
    {
        $this->createInvoice('sent', now()->subDays(5)->toDateString(), 1000, 1000);

        $service = app(PaymentReminderService::class);
        $overdue = $service->getOverdueInvoices($this->company->id);

        $this->assertCount(0, $overdue);
    }

    // ── Email reminder ──

    public function test_send_email_reminder(): void
    {
        Notification::fake();

        $invoice = $this->createInvoice('sent', now()->subDays(5)->toDateString());

        $response = $this->act()->postJson("/api/invoices/{$invoice->id}/remind-email");

        $response->assertOk()
            ->assertJsonPath('status', 'sent');

        $this->assertDatabaseHas('payment_reminders', [
            'invoice_id' => $invoice->id,
            'channel' => 'email',
            'status' => 'sent',
        ]);
    }

    public function test_email_fails_without_client_email(): void
    {
        $clientNoEmail = Client::factory()->create([
            'company_id' => $this->company->id,
            'email' => null,
        ]);
        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'client_id' => $clientNoEmail->id,
            'created_by' => $this->user->id,
            'invoice_number' => 'INV-NOEMAIL',
            'issue_date' => now()->subDays(30),
            'due_date' => now()->subDays(5),
            'total' => 1000,
            'status' => 'sent',
        ]);

        $response = $this->act()->postJson("/api/invoices/{$invoice->id}/remind-email");

        $response->assertOk()
            ->assertJsonPath('status', 'failed');
    }

    // ── WhatsApp ──

    public function test_generate_whatsapp_message(): void
    {
        $invoice = $this->createInvoice('sent', now()->subDays(3)->toDateString());

        $response = $this->act()->getJson("/api/invoices/{$invoice->id}/whatsapp-reminder");

        $response->assertOk()
            ->assertJsonStructure(['message', 'phone', 'whatsapp_url']);

        $this->assertStringContainsString($invoice->invoice_number, $response->json('message'));
        $this->assertStringContainsString('3 days overdue', $response->json('message'));
    }

    public function test_log_whatsapp_reminder(): void
    {
        $invoice = $this->createInvoice('sent', now()->subDays(3)->toDateString());

        $this->act()->postJson("/api/invoices/{$invoice->id}/remind-whatsapp")
            ->assertOk();

        $this->assertDatabaseHas('payment_reminders', [
            'invoice_id' => $invoice->id,
            'channel' => 'whatsapp',
        ]);
    }

    // ── Dashboard ──

    public function test_dashboard_returns_all_sections(): void
    {
        $this->createInvoice('sent', now()->subDays(5)->toDateString());
        $this->createInvoice('sent', now()->toDateString());
        $this->createInvoice('sent', now()->addDays(3)->toDateString());

        $response = $this->act()->getJson('/api/reminders/dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'overdue' => ['count', 'total', 'invoices'],
                'due_today' => ['count', 'total', 'invoices'],
                'due_soon' => ['count', 'total', 'invoices'],
                'recent_reminders',
            ]);

        $this->assertEquals(1, $response->json('overdue.count'));
        $this->assertEquals(1, $response->json('due_today.count'));
        $this->assertEquals(1, $response->json('due_soon.count'));
    }

    // ── Throttling ──

    public function test_process_overdue_throttles_reminders(): void
    {
        Notification::fake();

        $invoice = $this->createInvoice('sent', now()->subDays(5)->toDateString());

        // Create a recent reminder (1 day ago)
        PaymentReminder::create([
            'company_id' => $this->company->id,
            'invoice_id' => $invoice->id,
            'channel' => 'email',
            'recipient' => 'client@example.com',
            'status' => 'sent',
            'message_preview' => 'test',
            'sent_at' => now()->subDay(),
        ]);

        $service = app(PaymentReminderService::class);
        $sent = $service->processAllOverdue();

        $this->assertEquals(0, $sent); // throttled — last reminder < 3 days ago
    }

    public function test_process_overdue_sends_after_cooldown(): void
    {
        Notification::fake();

        $invoice = $this->createInvoice('sent', now()->subDays(10)->toDateString());

        PaymentReminder::create([
            'company_id' => $this->company->id,
            'invoice_id' => $invoice->id,
            'channel' => 'email',
            'recipient' => 'client@example.com',
            'status' => 'sent',
            'message_preview' => 'test',
            'sent_at' => now()->subDays(4), // 4 days ago — past cooldown
        ]);

        $service = app(PaymentReminderService::class);
        $sent = $service->processAllOverdue();

        $this->assertEquals(1, $sent);
    }

    // ── Auto-overdue status ──

    public function test_process_overdue_marks_sent_as_overdue(): void
    {
        Notification::fake();

        $invoice = $this->createInvoice('sent', now()->subDays(5)->toDateString());

        $service = app(PaymentReminderService::class);
        $service->processAllOverdue();

        $this->assertEquals('overdue', $invoice->fresh()->status);
    }

    // ── Artisan command ──

    public function test_artisan_command_runs(): void
    {
        Notification::fake();

        $this->createInvoice('sent', now()->subDays(5)->toDateString());

        $this->artisan('invoices:process-overdue')
            ->assertSuccessful();
    }
}
