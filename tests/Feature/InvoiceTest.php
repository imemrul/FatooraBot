<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
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
        $this->client = Client::factory()->create(['company_id' => $this->company->id]);
    }

    private function act()
    {
        return $this->actingAs($this->user, 'sanctum');
    }

    private function createInvoice(array $overrides = []): array
    {
        $payload = array_merge([
            'client_id' => $this->client->id,
            'issue_date' => '2025-01-01',
            'due_date' => '2025-01-31',
            'items' => [
                ['description' => 'Web Dev', 'quantity' => 10, 'unit_price' => 500, 'vat_rate' => 5],
                ['description' => 'Hosting', 'quantity' => 1, 'unit_price' => 200, 'vat_rate' => 5],
            ],
        ], $overrides);

        $response = $this->act()->postJson('/api/invoices', $payload);
        $response->assertStatus(201);

        return $response->json('data');
    }

    // ── CRUD ──

    public function test_create_invoice_with_items(): void
    {
        $data = $this->createInvoice();

        $this->assertEquals('draft', $data['status']);
        $this->assertCount(2, $data['items']);

        $invoice = Invoice::first();
        $this->assertEquals('5200.00', $invoice->subtotal);
        $this->assertEquals('260.00', $invoice->vat_amount);
        $this->assertEquals('5460.00', $invoice->total);
        $this->assertEquals('0.00', $invoice->paid_amount);
    }

    public function test_create_invoice_with_discount(): void
    {
        $data = $this->createInvoice(['discount' => 100]);

        $invoice = Invoice::first();
        $this->assertEquals('5360.00', $invoice->total); // 5200 + 260 - 100
    }

    public function test_list_invoices(): void
    {
        $this->createInvoice();

        $this->act()->getJson('/api/invoices')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_filter_invoices_by_status(): void
    {
        $inv = $this->createInvoice();
        $this->act()->postJson("/api/invoices/{$inv['id']}/send");

        $this->act()->getJson('/api/invoices?status=sent')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->act()->getJson('/api/invoices?status=paid')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_show_invoice_with_relations(): void
    {
        $inv = $this->createInvoice();

        $this->act()->getJson("/api/invoices/{$inv['id']}")
            ->assertOk()
            ->assertJsonStructure(['data' => ['client', 'items', 'payments']]);
    }

    public function test_update_draft_invoice(): void
    {
        $inv = $this->createInvoice();

        $this->act()->putJson("/api/invoices/{$inv['id']}", [
            'client_id' => $this->client->id,
            'issue_date' => '2025-02-01',
            'due_date' => '2025-02-28',
            'items' => [['description' => 'Updated', 'quantity' => 1, 'unit_price' => 1000, 'vat_rate' => 5]],
        ])->assertOk();

        $invoice = Invoice::find($inv['id']);
        $this->assertEquals('1000.00', $invoice->subtotal);
        $this->assertCount(1, $invoice->items);
    }

    public function test_cannot_delete_non_draft_invoice(): void
    {
        $inv = $this->createInvoice();
        $this->act()->postJson("/api/invoices/{$inv['id']}/send");

        $this->act()->deleteJson("/api/invoices/{$inv['id']}")
            ->assertForbidden();
    }

    // ── Send flow ──

    public function test_send_invoice_changes_status(): void
    {
        $inv = $this->createInvoice();

        $this->act()->postJson("/api/invoices/{$inv['id']}/send")
            ->assertOk()
            ->assertJsonPath('data.status', 'sent');
    }

    // ── Payments ──

    public function test_record_partial_payment(): void
    {
        $inv = $this->createInvoice();
        $this->act()->postJson("/api/invoices/{$inv['id']}/send");

        $response = $this->act()->postJson("/api/invoices/{$inv['id']}/payments", [
            'amount' => 2000,
            'method' => 'bank_transfer',
            'payment_date' => '2025-01-15',
            'reference' => 'TRF-001',
        ]);

        $response->assertStatus(201);

        $invoice = Invoice::find($inv['id']);
        $this->assertEquals('2000.00', $invoice->paid_amount);
        $this->assertEquals('sent', $invoice->status); // still sent, not fully paid
        $this->assertEquals(3460.00, $invoice->balance_due);
    }

    public function test_full_payment_marks_invoice_paid(): void
    {
        $inv = $this->createInvoice();
        $this->act()->postJson("/api/invoices/{$inv['id']}/send");

        $this->act()->postJson("/api/invoices/{$inv['id']}/payments", [
            'amount' => 5460,
            'method' => 'cash',
            'payment_date' => '2025-01-20',
        ]);

        $invoice = Invoice::find($inv['id']);
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals(0, $invoice->balance_due);
    }

    public function test_multiple_partial_payments(): void
    {
        $inv = $this->createInvoice();
        $this->act()->postJson("/api/invoices/{$inv['id']}/send");

        $this->act()->postJson("/api/invoices/{$inv['id']}/payments", [
            'amount' => 2000, 'method' => 'bank_transfer', 'payment_date' => '2025-01-10',
        ]);
        $this->act()->postJson("/api/invoices/{$inv['id']}/payments", [
            'amount' => 3460, 'method' => 'cheque', 'payment_date' => '2025-01-20',
        ]);

        $invoice = Invoice::find($inv['id']);
        $this->assertEquals('paid', $invoice->status);
        $this->assertCount(2, $invoice->payments);
    }

    public function test_payment_cannot_exceed_balance(): void
    {
        $inv = $this->createInvoice();
        $this->act()->postJson("/api/invoices/{$inv['id']}/send");

        $this->act()->postJson("/api/invoices/{$inv['id']}/payments", [
            'amount' => 99999,
            'method' => 'cash',
            'payment_date' => '2025-01-20',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    // ── PDF ──

    public function test_download_pdf(): void
    {
        $inv = $this->createInvoice();

        $response = $this->act()->get("/api/invoices/{$inv['id']}/pdf");

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    // ── Status transitions ──

    public function test_cancel_invoice(): void
    {
        $inv = $this->createInvoice();

        $this->act()->patchJson("/api/invoices/{$inv['id']}/status", ['status' => 'cancelled'])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    // ── Tenant isolation ──

    public function test_tenant_isolation(): void
    {
        $this->createInvoice();

        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()->create(['company_id' => $otherCompany->id]);
        $otherUser->assignRole('owner');

        $this->actingAs($otherUser, 'sanctum')
            ->getJson('/api/invoices')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    // ── Overdue detection ──

    public function test_overdue_flag(): void
    {
        $inv = $this->createInvoice(['due_date' => now()->subDays(5)->toDateString()]);
        $this->act()->postJson("/api/invoices/{$inv['id']}/send");

        $response = $this->act()->getJson("/api/invoices/{$inv['id']}");

        $response->assertOk()
            ->assertJsonPath('data.is_overdue', true);
    }
}
