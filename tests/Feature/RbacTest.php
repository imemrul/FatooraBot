<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->company = Company::factory()->create();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);
        $user->assignRole($role);

        return $user;
    }

    // ── Owner: full access ──

    public function test_owner_can_manage_customers(): void
    {
        $owner = $this->userWithRole('owner');

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/clients', ['name' => 'Test Client'])
            ->assertStatus(201);
    }

    public function test_owner_can_manage_inventory(): void
    {
        $owner = $this->userWithRole('owner');

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/products', [
                'name' => 'Widget',
                'unit_price' => 100,
            ])
            ->assertStatus(201);
    }

    public function test_owner_can_manage_invoices(): void
    {
        $owner = $this->userWithRole('owner');
        $client = Client::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/invoices', [
                'client_id' => $client->id,
                'issue_date' => '2025-01-01',
                'due_date' => '2025-01-31',
                'items' => [['description' => 'Service', 'quantity' => 1, 'unit_price' => 500, 'vat_rate' => 5]],
            ])
            ->assertStatus(201);
    }

    // ── Accountant: manage customers + invoices, view inventory ──

    public function test_accountant_can_manage_customers(): void
    {
        $accountant = $this->userWithRole('accountant');

        $this->actingAs($accountant, 'sanctum')
            ->postJson('/api/clients', ['name' => 'Acct Client'])
            ->assertStatus(201);
    }

    public function test_accountant_can_manage_invoices(): void
    {
        $accountant = $this->userWithRole('accountant');
        $client = Client::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($accountant, 'sanctum')
            ->postJson('/api/invoices', [
                'client_id' => $client->id,
                'issue_date' => '2025-01-01',
                'due_date' => '2025-01-31',
                'items' => [['description' => 'Service', 'quantity' => 1, 'unit_price' => 500, 'vat_rate' => 5]],
            ])
            ->assertStatus(201);
    }

    public function test_accountant_can_view_inventory(): void
    {
        $accountant = $this->userWithRole('accountant');

        $this->actingAs($accountant, 'sanctum')
            ->getJson('/api/products')
            ->assertOk();
    }

    public function test_accountant_cannot_manage_inventory(): void
    {
        $accountant = $this->userWithRole('accountant');

        $this->actingAs($accountant, 'sanctum')
            ->postJson('/api/products', ['name' => 'Widget', 'unit_price' => 100])
            ->assertForbidden();
    }

    // ── Warehouse Manager: manage inventory, view-only customers/invoices ──

    public function test_warehouse_manager_can_manage_inventory(): void
    {
        $wm = $this->userWithRole('warehouse_manager');

        $this->actingAs($wm, 'sanctum')
            ->postJson('/api/products', ['name' => 'Bolt', 'unit_price' => 5])
            ->assertStatus(201);
    }

    public function test_warehouse_manager_can_view_customers(): void
    {
        $wm = $this->userWithRole('warehouse_manager');

        $this->actingAs($wm, 'sanctum')
            ->getJson('/api/clients')
            ->assertOk();
    }

    public function test_warehouse_manager_cannot_manage_customers(): void
    {
        $wm = $this->userWithRole('warehouse_manager');

        $this->actingAs($wm, 'sanctum')
            ->postJson('/api/clients', ['name' => 'Blocked Client'])
            ->assertForbidden();
    }

    public function test_warehouse_manager_cannot_manage_invoices(): void
    {
        $wm = $this->userWithRole('warehouse_manager');
        $client = Client::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($wm, 'sanctum')
            ->postJson('/api/invoices', [
                'client_id' => $client->id,
                'issue_date' => '2025-01-01',
                'due_date' => '2025-01-31',
                'items' => [['description' => 'X', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 5]],
            ])
            ->assertForbidden();
    }

    // ── Salesman: manage customers + invoices, view inventory ──

    public function test_salesman_can_manage_customers(): void
    {
        $salesman = $this->userWithRole('salesman');

        $this->actingAs($salesman, 'sanctum')
            ->postJson('/api/clients', ['name' => 'Sales Client'])
            ->assertStatus(201);
    }

    public function test_salesman_can_view_invoices(): void
    {
        $salesman = $this->userWithRole('salesman');

        $this->actingAs($salesman, 'sanctum')
            ->getJson('/api/invoices')
            ->assertOk();
    }

    public function test_salesman_can_manage_invoices(): void
    {
        $salesman = $this->userWithRole('salesman');
        $client = Client::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($salesman, 'sanctum')
            ->postJson('/api/invoices', [
                'client_id' => $client->id,
                'issue_date' => '2025-01-01',
                'due_date' => '2025-01-31',
                'items' => [['description' => 'Sale', 'quantity' => 1, 'unit_price' => 200, 'vat_rate' => 5]],
            ])
            ->assertStatus(201);
    }

    public function test_salesman_cannot_manage_inventory(): void
    {
        $salesman = $this->userWithRole('salesman');

        $this->actingAs($salesman, 'sanctum')
            ->postJson('/api/products', ['name' => 'Blocked', 'unit_price' => 10])
            ->assertForbidden();
    }

    // ── Tenant isolation ──

    public function test_user_cannot_see_other_company_clients(): void
    {
        $owner = $this->userWithRole('owner');
        Client::factory()->count(2)->create(['company_id' => $this->company->id]);

        $otherCompany = Company::factory()->create();
        Client::factory()->count(3)->create(['company_id' => $otherCompany->id]);

        $this->actingAs($owner, 'sanctum')
            ->getJson('/api/clients')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_user_cannot_see_other_company_products(): void
    {
        $owner = $this->userWithRole('owner');
        Product::factory()->count(1)->create(['company_id' => $this->company->id]);

        $otherCompany = Company::factory()->create();
        Product::factory()->count(5)->create(['company_id' => $otherCompany->id]);

        $this->actingAs($owner, 'sanctum')
            ->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    // ── Helper method tests ──

    public function test_user_has_role_helper(): void
    {
        $owner = $this->userWithRole('owner');
        $accountant = $this->userWithRole('accountant');

        $this->assertTrue($owner->hasRole('owner'));
        $this->assertFalse($owner->hasRole('accountant'));
        $this->assertTrue($accountant->hasRole('accountant'));
    }

    public function test_user_can_access_helper(): void
    {
        $accountant = $this->userWithRole('accountant');

        $this->assertTrue($accountant->canAccess('manage_invoices'));
        $this->assertTrue($accountant->canAccess('view_inventory'));
        $this->assertFalse($accountant->canAccess('manage_inventory'));
        $this->assertFalse($accountant->canAccess('manage_users'));
    }

    public function test_user_can_access_any_helper(): void
    {
        $wm = $this->userWithRole('warehouse_manager');

        $this->assertTrue($wm->canAccessAny(['manage_inventory', 'manage_users']));
        $this->assertFalse($wm->canAccessAny(['manage_customers', 'manage_invoices']));
    }

    public function test_is_owner_helper(): void
    {
        $owner = $this->userWithRole('owner');
        $accountant = $this->userWithRole('accountant');

        $this->assertTrue($owner->isOwner());
        $this->assertFalse($accountant->isOwner());
    }

    // ── Middleware enforcement ──

    public function test_unauthenticated_user_gets_401(): void
    {
        $this->getJson('/api/clients')->assertUnauthorized();
        $this->postJson('/api/products', [])->assertUnauthorized();
    }
}
