<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Client;
use App\Models\Company;
use App\Models\InventoryLevel;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Webhook;
use App\Services\WebhookService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->user->assignRole('owner');

        $this->token = ApiToken::generateToken();
        ApiToken::create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'name' => 'Test Token',
            'token' => $this->token,
            'scopes' => ['*'],
            'rate_limit' => 60,
        ]);
    }

    private function api(string $method, string $uri, array $data = [])
    {
        return $this->withHeader('Authorization', "Bearer {$this->token}")
            ->json($method, "/api/v1{$uri}", $data);
    }

    // ── Token auth ──

    public function test_request_without_token_returns_401(): void
    {
        $this->getJson('/api/v1/products')->assertStatus(401);
    }

    public function test_invalid_token_returns_401(): void
    {
        $this->withHeader('Authorization', 'Bearer invalid-token-here')
            ->getJson('/api/v1/products')
            ->assertStatus(401);
    }

    public function test_valid_token_authenticates(): void
    {
        $this->api('GET', '/products')->assertOk();
    }

    public function test_expired_token_returns_401(): void
    {
        $expired = ApiToken::generateToken();
        ApiToken::create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'name' => 'Expired',
            'token' => $expired,
            'scopes' => ['*'],
            'expires_at' => now()->subDay(),
        ]);

        $this->withHeader('Authorization', "Bearer {$expired}")
            ->getJson('/api/v1/products')
            ->assertStatus(401);
    }

    public function test_inactive_token_returns_401(): void
    {
        $inactive = ApiToken::generateToken();
        ApiToken::create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'name' => 'Inactive',
            'token' => $inactive,
            'scopes' => ['*'],
            'is_active' => false,
        ]);

        $this->withHeader('Authorization', "Bearer {$inactive}")
            ->getJson('/api/v1/products')
            ->assertStatus(401);
    }

    // ── Scopes ──

    public function test_scope_enforcement(): void
    {
        $readOnly = ApiToken::generateToken();
        ApiToken::create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'name' => 'Read Only',
            'token' => $readOnly,
            'scopes' => ['products.read'],
        ]);

        $this->withHeader('Authorization', "Bearer {$readOnly}")
            ->getJson('/api/v1/products')
            ->assertOk();

        $this->withHeader('Authorization', "Bearer {$readOnly}")
            ->postJson('/api/v1/products', ['name' => 'Test', 'unit_price' => 10])
            ->assertStatus(403);
    }

    // ── Token management ──

    public function test_create_api_token(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/tokens', [
                'name' => 'My Integration',
                'scopes' => ['products.read', 'invoices.read'],
                'rate_limit' => 100,
            ])
            ->assertStatus(201)
            ->assertJsonStructure(['token', 'id', 'name', 'scopes']);
    }

    public function test_list_api_tokens(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/tokens')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_revoke_api_token(): void
    {
        $tokenModel = ApiToken::first();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/tokens/{$tokenModel->id}")
            ->assertOk();

        $this->assertFalse($tokenModel->fresh()->is_active);
    }

    // ── Products ──

    public function test_list_products(): void
    {
        Product::factory()->count(3)->create(['company_id' => $this->company->id]);

        $this->api('GET', '/products')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.object', 'product');
    }

    public function test_create_product(): void
    {
        $this->api('POST', '/products', [
            'name' => 'API Widget',
            'sku' => 'API-001',
            'unit_price' => 99.99,
            'cost_price' => 45.00,
        ])
            ->assertStatus(201)
            ->assertJsonPath('data.object', 'product')
            ->assertJsonPath('data.name', 'API Widget');
    }

    public function test_show_product(): void
    {
        $product = Product::factory()->create(['company_id' => $this->company->id]);

        $this->api('GET', "/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $product->id);
    }

    // ── Customers ──

    public function test_list_customers(): void
    {
        Client::factory()->count(2)->create(['company_id' => $this->company->id]);

        $this->api('GET', '/customers')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.object', 'customer');
    }

    public function test_create_customer(): void
    {
        $this->api('POST', '/customers', [
            'name' => 'API Client LLC',
            'email' => 'api@client.ae',
            'phone' => '+971501111111',
            'payment_terms' => 30,
        ])
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'API Client LLC');
    }

    // ── Invoices ──

    public function test_create_invoice_via_api(): void
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);

        $this->api('POST', '/invoices', [
            'customer_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'line_items' => [
                ['description' => 'API Service', 'quantity' => 5, 'unit_price' => 200, 'vat_rate' => 5],
            ],
        ])
            ->assertStatus(201)
            ->assertJsonPath('data.object', 'invoice')
            ->assertJsonPath('data.status', 'draft');
    }

    public function test_list_invoices_with_filter(): void
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);

        // Create via internal API to get proper totals
        $this->api('POST', '/invoices', [
            'customer_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'line_items' => [['description' => 'X', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 5]],
        ]);

        $this->api('GET', '/invoices?status=draft')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    // ── Inventory ──

    public function test_stock_in_via_api(): void
    {
        $product = Product::factory()->create(['company_id' => $this->company->id]);
        $warehouse = Warehouse::factory()->create(['company_id' => $this->company->id]);

        $this->api('POST', '/inventory/stock-in', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 100,
            'reference' => 'API-PO-001',
        ])
            ->assertStatus(201)
            ->assertJsonPath('type', 'stock_in');

        $this->assertDatabaseHas('inventory_levels', [
            'product_id' => $product->id,
            'quantity' => 100,
        ]);
    }

    public function test_inventory_levels(): void
    {
        $product = Product::factory()->create(['company_id' => $this->company->id]);
        $warehouse = Warehouse::factory()->create(['company_id' => $this->company->id]);
        InventoryLevel::create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 50,
        ]);

        $this->api('GET', '/inventory')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.object', 'inventory_level');
    }

    // ── Webhooks ──

    public function test_create_webhook(): void
    {
        $this->api('POST', '/webhooks', [
            'url' => 'https://example.com/webhook',
            'events' => ['invoice.created', 'customer.created'],
        ])
            ->assertStatus(201)
            ->assertJsonStructure(['webhook', 'secret', 'message']);
    }

    public function test_list_webhooks(): void
    {
        Webhook::create([
            'company_id' => $this->company->id,
            'url' => 'https://example.com/hook',
            'secret' => 'test-secret',
            'events' => ['*'],
        ]);

        $this->api('GET', '/webhooks')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_webhook_events_list(): void
    {
        $this->api('GET', '/webhooks/events')
            ->assertOk()
            ->assertJsonStructure(['events']);
    }

    public function test_webhook_dispatch_on_product_create(): void
    {
        Http::fake(['*' => Http::response('OK', 200)]);

        Webhook::create([
            'company_id' => $this->company->id,
            'url' => 'https://example.com/hook',
            'secret' => 'test-secret',
            'events' => ['product.created'],
        ]);

        $this->api('POST', '/products', [
            'name' => 'Webhook Test Product',
            'unit_price' => 50,
        ])->assertStatus(201);

        Http::assertSentCount(1);
        $this->assertDatabaseHas('webhook_logs', [
            'event' => 'product.created',
            'status' => 'success',
        ]);
    }

    public function test_webhook_auto_disables_after_failures(): void
    {
        $webhook = Webhook::create([
            'company_id' => $this->company->id,
            'url' => 'https://example.com/hook',
            'secret' => 'test-secret',
            'events' => ['*'],
            'failure_count' => 9,
        ]);

        Http::fake(['*' => Http::response('Error', 500)]);

        $service = app(WebhookService::class);
        $service->send($webhook, 'test.event', ['test' => true]);

        $this->assertFalse($webhook->fresh()->is_active);
        $this->assertEquals(10, $webhook->fresh()->failure_count);
    }

    // ── Tenant isolation ──

    public function test_api_token_scoped_to_company(): void
    {
        Product::factory()->count(3)->create(['company_id' => $this->company->id]);

        $otherCompany = Company::factory()->create();
        Product::factory()->count(5)->create(['company_id' => $otherCompany->id]);

        $this->api('GET', '/products')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }
}
