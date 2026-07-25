<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\InventoryLevel;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Company $company;
    private Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->company = Company::factory()->create();
        $this->owner = User::factory()->create(['company_id' => $this->company->id]);
        $this->owner->assignRole('owner');
        $this->warehouse = Warehouse::factory()->create(['company_id' => $this->company->id]);
    }

    private function act()
    {
        return $this->actingAs($this->owner, 'sanctum');
    }

    // ── Products with new fields ──

    public function test_create_product_with_sku_and_cost(): void
    {
        $this->act()->postJson('/api/products', [
            'sku' => 'WIDGET-001',
            'barcode' => '1234567890123',
            'name' => 'Steel Widget',
            'unit_price' => 150.00,
            'cost_price' => 80.00,
            'vat_rate' => 5,
            'low_stock_threshold' => 20,
        ])
            ->assertStatus(201)
            ->assertJsonPath('data.sku', 'WIDGET-001')
            ->assertJsonPath('data.cost_price', '80.00')
            ->assertJsonPath('data.low_stock_threshold', 20);
    }

    public function test_sku_unique_per_company(): void
    {
        Product::factory()->create([
            'company_id' => $this->company->id,
            'sku' => 'DUP-SKU',
        ]);

        $this->act()->postJson('/api/products', [
            'sku' => 'DUP-SKU',
            'name' => 'Another',
            'unit_price' => 10,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sku']);
    }

    public function test_same_sku_allowed_in_different_company(): void
    {
        Product::factory()->create([
            'company_id' => Company::factory()->create()->id,
            'sku' => 'SHARED-SKU',
        ]);

        $this->act()->postJson('/api/products', [
            'sku' => 'SHARED-SKU',
            'name' => 'My Product',
            'unit_price' => 10,
        ])->assertStatus(201);
    }

    // ── Warehouses ──

    public function test_create_warehouse(): void
    {
        $this->act()->postJson('/api/warehouses', [
            'name' => 'Dubai Main',
            'location' => 'Al Quoz Industrial Area',
        ])
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'Dubai Main');
    }

    public function test_cannot_delete_warehouse_with_stock(): void
    {
        $product = Product::factory()->create(['company_id' => $this->company->id]);
        InventoryLevel::create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 50,
        ]);

        $this->act()->deleteJson("/api/warehouses/{$this->warehouse->id}")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Cannot delete warehouse with stock. Transfer stock first.');
    }

    // ── Stock In ──

    public function test_stock_in(): void
    {
        $product = Product::factory()->create(['company_id' => $this->company->id]);

        $this->act()->postJson('/api/inventory/move', [
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => 'stock_in',
            'quantity' => 100,
            'reference' => 'PO-001',
        ])
            ->assertStatus(201)
            ->assertJsonPath('data.type', 'stock_in')
            ->assertJsonPath('data.quantity', 100);

        $this->assertDatabaseHas('inventory_levels', [
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 100,
        ]);
    }

    // ── Stock Out ──

    public function test_stock_out(): void
    {
        $product = Product::factory()->create(['company_id' => $this->company->id]);
        InventoryLevel::create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 50,
        ]);

        $this->act()->postJson('/api/inventory/move', [
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => 'stock_out',
            'quantity' => 20,
        ])->assertStatus(201);

        $this->assertDatabaseHas('inventory_levels', [
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 30,
        ]);
    }

    public function test_stock_out_fails_insufficient_stock(): void
    {
        $product = Product::factory()->create(['company_id' => $this->company->id]);
        InventoryLevel::create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 5,
        ]);

        $this->act()->postJson('/api/inventory/move', [
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => 'stock_out',
            'quantity' => 10,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }

    // ── Transfer ──

    public function test_transfer_stock(): void
    {
        $product = Product::factory()->create(['company_id' => $this->company->id]);
        $warehouseB = Warehouse::factory()->create(['company_id' => $this->company->id]);

        InventoryLevel::create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 80,
        ]);

        $this->act()->postJson('/api/inventory/move', [
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'to_warehouse_id' => $warehouseB->id,
            'type' => 'transfer',
            'quantity' => 30,
        ])->assertStatus(201);

        $this->assertDatabaseHas('inventory_levels', [
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 50,
        ]);
        $this->assertDatabaseHas('inventory_levels', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouseB->id,
            'quantity' => 30,
        ]);
    }

    public function test_transfer_requires_destination(): void
    {
        $product = Product::factory()->create(['company_id' => $this->company->id]);

        $this->act()->postJson('/api/inventory/move', [
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => 'transfer',
            'quantity' => 10,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['to_warehouse_id']);
    }

    public function test_transfer_rejects_same_warehouse(): void
    {
        $product = Product::factory()->create(['company_id' => $this->company->id]);

        $this->act()->postJson('/api/inventory/move', [
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'to_warehouse_id' => $this->warehouse->id,
            'type' => 'transfer',
            'quantity' => 10,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['to_warehouse_id']);
    }

    // ── Alerts ──

    public function test_low_stock_alert(): void
    {
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
            'low_stock_threshold' => 20,
        ]);
        InventoryLevel::create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 5,
        ]);

        $response = $this->act()->getJson('/api/inventory/alerts');

        $response->assertOk();
        $lowStock = collect($response->json('low_stock'));
        $this->assertTrue($lowStock->contains('id', $product->id));
    }

    public function test_out_of_stock_alert(): void
    {
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);
        // No inventory levels created = out of stock

        $response = $this->act()->getJson('/api/inventory/alerts');

        $response->assertOk();
        $oos = collect($response->json('out_of_stock'));
        $this->assertTrue($oos->contains('id', $product->id));
    }

    // ── Movements list ──

    public function test_list_movements(): void
    {
        $product = Product::factory()->create(['company_id' => $this->company->id]);

        $this->act()->postJson('/api/inventory/move', [
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => 'stock_in',
            'quantity' => 50,
        ]);

        $this->act()->getJson('/api/inventory/movements')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    // ── Inventory levels list ──

    public function test_list_inventory_levels(): void
    {
        $product = Product::factory()->create(['company_id' => $this->company->id]);
        InventoryLevel::create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 100,
        ]);

        $this->act()->getJson('/api/inventory/levels')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    // ── Tenant isolation ──

    public function test_cannot_see_other_company_inventory(): void
    {
        $otherCompany = Company::factory()->create();
        $otherWarehouse = Warehouse::factory()->create(['company_id' => $otherCompany->id]);
        $otherProduct = Product::factory()->create(['company_id' => $otherCompany->id]);
        InventoryLevel::create([
            'company_id' => $otherCompany->id,
            'product_id' => $otherProduct->id,
            'warehouse_id' => $otherWarehouse->id,
            'quantity' => 999,
        ]);

        $this->act()->getJson('/api/inventory/levels')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    // ── RBAC ──

    public function test_warehouse_manager_can_manage_stock(): void
    {
        $wm = User::factory()->create(['company_id' => $this->company->id]);
        $wm->assignRole('warehouse_manager');
        $product = Product::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($wm, 'sanctum')
            ->postJson('/api/inventory/move', [
                'product_id' => $product->id,
                'warehouse_id' => $this->warehouse->id,
                'type' => 'stock_in',
                'quantity' => 25,
            ])
            ->assertStatus(201);
    }

    public function test_salesman_cannot_manage_stock(): void
    {
        $salesman = User::factory()->create(['company_id' => $this->company->id]);
        $salesman->assignRole('salesman');
        $product = Product::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($salesman, 'sanctum')
            ->postJson('/api/inventory/move', [
                'product_id' => $product->id,
                'warehouse_id' => $this->warehouse->id,
                'type' => 'stock_in',
                'quantity' => 10,
            ])
            ->assertForbidden();
    }
}
