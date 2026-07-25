<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
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

    // ── Auto-logging ──

    public function test_logs_product_created(): void
    {
        $this->act()->postJson('/api/products', [
            'name' => 'Audit Widget',
            'unit_price' => 50,
        ])->assertStatus(201);

        $this->assertDatabaseHas('audit_logs', [
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'action' => 'created',
            'auditable_type' => Product::class,
        ]);
    }

    public function test_logs_product_updated_with_diff(): void
    {
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Old Name',
            'unit_price' => 100,
        ]);

        // Clear the "created" log
        AuditLog::truncate();

        $this->act()->putJson("/api/products/{$product->id}", [
            'name' => 'New Name',
            'unit_price' => 200,
        ])->assertOk();

        $log = AuditLog::where('action', 'updated')->first();

        $this->assertNotNull($log);
        $this->assertEquals('Old Name', $log->old_values['name']);
        $this->assertEquals('New Name', $log->new_values['name']);
    }

    public function test_logs_product_deleted(): void
    {
        $product = Product::factory()->create(['company_id' => $this->company->id]);

        $this->act()->deleteJson("/api/products/{$product->id}")
            ->assertNoContent();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'deleted',
            'auditable_type' => Product::class,
            'auditable_id' => $product->id,
        ]);
    }

    public function test_logs_client_created(): void
    {
        $this->act()->postJson('/api/clients', [
            'name' => 'Audit Client',
        ])->assertStatus(201);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'auditable_type' => Client::class,
            'auditable_label' => 'Audit Client',
        ]);
    }

    public function test_logs_invoice_status_change(): void
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);

        $res = $this->act()->postJson('/api/invoices', [
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'items' => [['description' => 'X', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 5]],
        ]);

        $invoiceId = $res->json('data.id');
        AuditLog::truncate();

        $this->act()->patchJson("/api/invoices/{$invoiceId}/status", ['status' => 'sent']);

        $log = AuditLog::where('action', 'updated')
            ->where('auditable_type', Invoice::class)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('draft', $log->old_values['status']);
        $this->assertEquals('sent', $log->new_values['status']);
    }

    public function test_captures_user_info(): void
    {
        $this->act()->postJson('/api/products', [
            'name' => 'User Track',
            'unit_price' => 10,
        ]);

        $log = AuditLog::first();

        $this->assertEquals($this->user->id, $log->user_id);
        $this->assertEquals($this->user->name, $log->user_name);
        $this->assertNotNull($log->ip_address);
    }

    // ── API endpoints ──

    public function test_list_audit_logs(): void
    {
        Product::factory()->count(3)->create(['company_id' => $this->company->id]);

        $this->act()->getJson('/api/audit-logs')
            ->assertOk()
            ->assertJsonPath('total', 3);
    }

    public function test_filter_by_model(): void
    {
        Product::factory()->create(['company_id' => $this->company->id]);
        Client::factory()->create(['company_id' => $this->company->id]);

        $this->act()->getJson('/api/audit-logs?model=product')
            ->assertOk()
            ->assertJsonPath('total', 1);
    }

    public function test_filter_by_action(): void
    {
        $product = Product::factory()->create(['company_id' => $this->company->id]);
        $product->update(['name' => 'Updated']);

        $this->act()->getJson('/api/audit-logs?action=updated')
            ->assertOk()
            ->assertJsonPath('total', 1);
    }

    public function test_filter_by_search(): void
    {
        Product::factory()->create(['company_id' => $this->company->id, 'name' => 'Searchable Widget']);

        $this->act()->getJson('/api/audit-logs?search=Searchable')
            ->assertOk()
            ->assertJsonPath('total', 1);
    }

    public function test_show_audit_log_detail(): void
    {
        Product::factory()->create(['company_id' => $this->company->id]);
        $log = AuditLog::first();

        $this->act()->getJson("/api/audit-logs/{$log->id}")
            ->assertOk()
            ->assertJsonStructure(['id', 'user_name', 'action', 'model', 'old_values', 'new_values', 'ip_address']);
    }

    public function test_stats_endpoint(): void
    {
        Product::factory()->count(2)->create(['company_id' => $this->company->id]);
        Client::factory()->create(['company_id' => $this->company->id]);

        $this->act()->getJson('/api/audit-logs/stats')
            ->assertOk()
            ->assertJsonStructure(['today_count', 'by_action', 'by_model', 'top_users']);
    }

    // ── Tenant isolation ──

    public function test_tenant_isolation(): void
    {
        Product::factory()->create(['company_id' => $this->company->id]);

        $other = Company::factory()->create();
        $otherUser = User::factory()->create(['company_id' => $other->id]);
        $otherUser->assignRole('owner');

        // Create product in other company context
        $this->actingAs($otherUser, 'sanctum')
            ->postJson('/api/products', ['name' => 'Other', 'unit_price' => 10]);

        // Original user should only see their own logs
        $this->act()->getJson('/api/audit-logs')
            ->assertOk()
            ->assertJsonPath('total', 1);
    }
}
