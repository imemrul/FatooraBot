<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\InventoryLevel;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Company $company;
    private Client $client;
    private Warehouse $warehouse;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->user->assignRole('owner');
        $this->client = Client::factory()->create(['company_id' => $this->company->id]);
        $this->warehouse = Warehouse::factory()->create(['company_id' => $this->company->id]);
        $this->product = Product::factory()->create(['company_id' => $this->company->id, 'unit_price' => 100]);

        InventoryLevel::create([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 200,
        ]);
    }

    private function act()
    {
        return $this->actingAs($this->user, 'sanctum');
    }

    private function orderPayload(array $overrides = []): array
    {
        return array_merge([
            'client_id' => $this->client->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(),
            'delivery_date' => now()->addDays(7)->toDateString(),
            'items' => [[
                'product_id' => $this->product->id,
                'description' => $this->product->name,
                'quantity' => 10,
                'unit_price' => 100,
                'vat_rate' => 5,
            ]],
        ], $overrides);
    }

    private function createOrder(array $overrides = []): array
    {
        $response = $this->act()->postJson('/api/sales-orders', $this->orderPayload($overrides));
        $response->assertStatus(201);
        return $response->json('data');
    }

    // ── CRUD ──

    public function test_create_sales_order(): void
    {
        $data = $this->createOrder();

        $this->assertEquals('draft', $data['status']);
        $this->assertCount(1, $data['items']);
        $this->assertStringStartsWith('SO-', $data['order_number']);

        $order = SalesOrder::first();
        $this->assertEquals('1000.00', $order->subtotal);
        $this->assertEquals('50.00', $order->vat_amount);
        $this->assertEquals('1050.00', $order->total);
    }

    public function test_list_sales_orders(): void
    {
        $this->createOrder();

        $this->act()->getJson('/api/sales-orders')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_filter_by_status(): void
    {
        $this->createOrder();

        $this->act()->getJson('/api/sales-orders?status=draft')
            ->assertJsonCount(1, 'data');

        $this->act()->getJson('/api/sales-orders?status=confirmed')
            ->assertJsonCount(0, 'data');
    }

    public function test_update_draft_order(): void
    {
        $data = $this->createOrder();

        $this->act()->putJson("/api/sales-orders/{$data['id']}", $this->orderPayload([
            'items' => [[
                'product_id' => $this->product->id,
                'description' => 'Updated item',
                'quantity' => 5,
                'unit_price' => 200,
                'vat_rate' => 5,
            ]],
        ]))->assertOk();

        $order = SalesOrder::find($data['id']);
        $this->assertEquals('1000.00', $order->subtotal);
        $this->assertCount(1, $order->items);
    }

    public function test_cannot_update_confirmed_order(): void
    {
        $data = $this->createOrder();
        $this->act()->postJson("/api/sales-orders/{$data['id']}/confirm");

        $this->act()->putJson("/api/sales-orders/{$data['id']}", $this->orderPayload())
            ->assertStatus(422);
    }

    public function test_delete_draft_order(): void
    {
        $data = $this->createOrder();

        $this->act()->deleteJson("/api/sales-orders/{$data['id']}")
            ->assertNoContent();
    }

    public function test_cannot_delete_confirmed_order(): void
    {
        $data = $this->createOrder();
        $this->act()->postJson("/api/sales-orders/{$data['id']}/confirm");

        $this->act()->deleteJson("/api/sales-orders/{$data['id']}")
            ->assertStatus(422);
    }

    // ── Confirm (reserves stock) ──

    public function test_confirm_reserves_stock(): void
    {
        $data = $this->createOrder();

        $this->act()->postJson("/api/sales-orders/{$data['id']}/confirm")
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed');

        $level = InventoryLevel::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();

        $this->assertEquals(190, $level->quantity); // 200 - 10
    }

    public function test_confirm_fails_without_warehouse(): void
    {
        $data = $this->createOrder(['warehouse_id' => null]);

        $this->act()->postJson("/api/sales-orders/{$data['id']}/confirm")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['warehouse_id']);
    }

    public function test_confirm_fails_insufficient_stock(): void
    {
        $data = $this->createOrder([
            'items' => [[
                'product_id' => $this->product->id,
                'description' => $this->product->name,
                'quantity' => 999,
                'unit_price' => 100,
                'vat_rate' => 5,
            ]],
        ]);

        $this->act()->postJson("/api/sales-orders/{$data['id']}/confirm")
            ->assertStatus(422);
    }

    // ── Deliver ──

    public function test_deliver_confirmed_order(): void
    {
        $data = $this->createOrder();
        $this->act()->postJson("/api/sales-orders/{$data['id']}/confirm");

        $this->act()->postJson("/api/sales-orders/{$data['id']}/deliver")
            ->assertOk()
            ->assertJsonPath('data.status', 'delivered');
    }

    public function test_cannot_deliver_draft_order(): void
    {
        $data = $this->createOrder();

        $this->act()->postJson("/api/sales-orders/{$data['id']}/deliver")
            ->assertStatus(422);
    }

    // ── Cancel (releases stock) ──

    public function test_cancel_confirmed_releases_stock(): void
    {
        $data = $this->createOrder();
        $this->act()->postJson("/api/sales-orders/{$data['id']}/confirm");

        $level = InventoryLevel::where('product_id', $this->product->id)->first();
        $this->assertEquals(190, $level->quantity);

        $this->act()->postJson("/api/sales-orders/{$data['id']}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertEquals(200, $level->fresh()->quantity); // restored
    }

    public function test_cancel_draft_no_stock_change(): void
    {
        $data = $this->createOrder();

        $this->act()->postJson("/api/sales-orders/{$data['id']}/cancel")
            ->assertOk();

        $level = InventoryLevel::where('product_id', $this->product->id)->first();
        $this->assertEquals(200, $level->quantity); // unchanged
    }

    public function test_cannot_cancel_delivered_order(): void
    {
        $data = $this->createOrder();
        $this->act()->postJson("/api/sales-orders/{$data['id']}/confirm");
        $this->act()->postJson("/api/sales-orders/{$data['id']}/deliver");

        $this->act()->postJson("/api/sales-orders/{$data['id']}/cancel")
            ->assertStatus(422);
    }

    // ── Convert to Invoice ──

    public function test_convert_to_invoice(): void
    {
        $data = $this->createOrder();
        $this->act()->postJson("/api/sales-orders/{$data['id']}/confirm");

        $response = $this->act()->postJson("/api/sales-orders/{$data['id']}/convert-to-invoice");

        $response->assertStatus(201)
            ->assertJsonStructure(['order', 'invoice']);

        $this->assertDatabaseCount('invoices', 1);

        $invoice = Invoice::first();
        $this->assertEquals('draft', $invoice->status);
        $this->assertEquals($this->client->id, $invoice->client_id);

        $order = SalesOrder::find($data['id']);
        $this->assertEquals($invoice->id, $order->invoice_id);
    }

    public function test_cannot_convert_draft_to_invoice(): void
    {
        $data = $this->createOrder();

        $this->act()->postJson("/api/sales-orders/{$data['id']}/convert-to-invoice")
            ->assertStatus(422);
    }

    public function test_cannot_convert_twice(): void
    {
        $data = $this->createOrder();
        $this->act()->postJson("/api/sales-orders/{$data['id']}/confirm");
        $this->act()->postJson("/api/sales-orders/{$data['id']}/convert-to-invoice");

        $this->act()->postJson("/api/sales-orders/{$data['id']}/convert-to-invoice")
            ->assertStatus(422);
    }

    // ── Tenant isolation ──

    public function test_tenant_isolation(): void
    {
        $this->createOrder();

        $other = Company::factory()->create();
        $otherUser = User::factory()->create(['company_id' => $other->id]);
        $otherUser->assignRole('owner');

        $this->actingAs($otherUser, 'sanctum')
            ->getJson('/api/sales-orders')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
