<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->user->assignRole('owner');
    }

    private function act()
    {
        return $this->actingAs($this->user, 'sanctum');
    }

    private function createClientWithInvoice(float $total, float $paid, string $status = 'sent', ?string $dueDate = null): array
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);

        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'client_id' => $client->id,
            'created_by' => $this->user->id,
            'invoice_number' => 'INV-' . fake()->unique()->numerify('######'),
            'issue_date' => now()->subDays(10),
            'due_date' => $dueDate ?? now()->addDays(20),
            'total' => $total,
            'paid_amount' => $paid,
            'status' => $status,
        ]);

        return [$client, $invoice];
    }

    // ── CRUD ──

    public function test_create_client_with_crm_fields(): void
    {
        $this->act()->postJson('/api/clients', [
            'name' => 'Al Futtaim Trading',
            'contact_person' => 'Ahmed Hassan',
            'email' => 'ahmed@futtaim.ae',
            'phone' => '+971501234567',
            'tax_registration_number' => '100000000000003',
            'credit_limit' => 50000,
            'payment_terms' => 45,
            'address' => 'Sheikh Zayed Road',
            'city' => 'Dubai',
            'notes' => 'VIP customer',
        ])
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'Al Futtaim Trading')
            ->assertJsonPath('data.contact_person', 'Ahmed Hassan')
            ->assertJsonPath('data.credit_limit', '50000.00')
            ->assertJsonPath('data.payment_terms', 45);
    }

    public function test_update_client(): void
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);

        $this->act()->putJson("/api/clients/{$client->id}", [
            'name' => 'Updated Corp',
            'credit_limit' => 75000,
            'payment_terms' => 60,
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Corp')
            ->assertJsonPath('data.credit_limit', '75000.00');
    }

    public function test_list_clients(): void
    {
        Client::factory()->count(3)->create(['company_id' => $this->company->id]);

        $this->act()->getJson('/api/clients')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_search_clients(): void
    {
        Client::factory()->create(['company_id' => $this->company->id, 'name' => 'Alpha Trading']);
        Client::factory()->create(['company_id' => $this->company->id, 'name' => 'Beta Corp']);
        Client::factory()->create(['company_id' => $this->company->id, 'name' => 'Alpha Logistics']);

        $this->act()->getJson('/api/clients?search=alpha')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_cannot_delete_client_with_active_invoices(): void
    {
        [$client] = $this->createClientWithInvoice(1000, 0, 'sent');

        $this->act()->deleteJson("/api/clients/{$client->id}")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Cannot delete customer with active invoices.');
    }

    public function test_can_delete_client_without_invoices(): void
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);

        $this->act()->deleteJson("/api/clients/{$client->id}")
            ->assertNoContent();
    }

    // ── Financial computed fields ──

    public function test_outstanding_balance_computed(): void
    {
        [$client] = $this->createClientWithInvoice(5000, 2000, 'sent');

        $response = $this->act()->getJson("/api/clients/{$client->id}");

        $response->assertOk()
            ->assertJsonPath('data.total_invoiced', 5000.0)
            ->assertJsonPath('data.total_paid', 2000.0)
            ->assertJsonPath('data.outstanding_balance', 3000.0);
    }

    public function test_overdue_detection(): void
    {
        [$client] = $this->createClientWithInvoice(
            total: 3000,
            paid: 0,
            status: 'sent',
            dueDate: now()->subDays(5)->toDateString(),
        );

        $response = $this->act()->getJson("/api/clients/{$client->id}");

        $response->assertOk();
        $this->assertEquals(3000.0, $response->json('data.overdue_amount'));
        $this->assertEquals(1, $response->json('data.overdue_invoice_count'));
    }

    public function test_credit_limit_exceeded(): void
    {
        $client = Client::factory()->create([
            'company_id' => $this->company->id,
            'credit_limit' => 1000,
        ]);

        Invoice::create([
            'company_id' => $this->company->id,
            'client_id' => $client->id,
            'created_by' => $this->user->id,
            'invoice_number' => 'INV-OVER-001',
            'issue_date' => now(),
            'due_date' => now()->addDays(30),
            'total' => 5000,
            'paid_amount' => 0,
            'status' => 'sent',
        ]);

        $this->act()->getJson("/api/clients/{$client->id}")
            ->assertOk()
            ->assertJsonPath('data.over_credit_limit', true);
    }

    // ── Ledger ──

    public function test_client_ledger(): void
    {
        [$client] = $this->createClientWithInvoice(1000, 500, 'sent');

        $this->act()->getJson("/api/clients/{$client->id}/ledger")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_ledger_excludes_draft_invoices(): void
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);

        Invoice::create([
            'company_id' => $this->company->id,
            'client_id' => $client->id,
            'created_by' => $this->user->id,
            'invoice_number' => 'INV-DRAFT-001',
            'issue_date' => now(),
            'due_date' => now()->addDays(30),
            'total' => 1000,
            'status' => 'draft',
        ]);

        $this->act()->getJson("/api/clients/{$client->id}/ledger")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    // ── Statement ──

    public function test_client_statement(): void
    {
        [$client] = $this->createClientWithInvoice(2000, 800, 'sent');

        $response = $this->act()->getJson("/api/clients/{$client->id}/statement");

        $response->assertOk()
            ->assertJsonStructure(['entries', 'total_invoiced', 'total_paid', 'outstanding_balance']);

        $this->assertCount(2, $response->json('entries')); // invoice + payment
        $this->assertEquals(2000.0, $response->json('total_invoiced'));
        $this->assertEquals(800.0, $response->json('total_paid'));
        $this->assertEquals(1200.0, $response->json('outstanding_balance'));
    }

    // ── Tenant isolation ──

    public function test_tenant_isolation(): void
    {
        Client::factory()->count(2)->create(['company_id' => $this->company->id]);

        $otherCompany = Company::factory()->create();
        Client::factory()->count(3)->create(['company_id' => $otherCompany->id]);

        $this->act()->getJson('/api/clients')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
