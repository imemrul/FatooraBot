<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use App\Services\WhatsAppOrderParserService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppOrderParserTest extends TestCase
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

    private function seedData(): void
    {
        Client::factory()->create(['company_id' => $this->company->id, 'name' => 'Ahmed Trading LLC', 'contact_person' => 'Ahmed Hassan']);
        Client::factory()->create(['company_id' => $this->company->id, 'name' => 'Dubai Electronics']);
        Product::factory()->create(['company_id' => $this->company->id, 'name' => 'USB Charger', 'sku' => 'CHG-001', 'unit_price' => 25.00]);
        Product::factory()->create(['company_id' => $this->company->id, 'name' => 'Phone Cover', 'sku' => 'COV-001', 'unit_price' => 15.00]);
        Product::factory()->create(['company_id' => $this->company->id, 'name' => 'Screen Protector', 'sku' => 'SCR-001', 'unit_price' => 10.00]);
    }

    // ── Parser unit tests via service ──

    public function test_extracts_customer_name(): void
    {
        $this->seedData();

        $parser = app(WhatsAppOrderParserService::class);
        $result = $parser->parse('Ahmed need 5 chargers tomorrow');

        $this->assertNotNull($result->customer_name);
        $this->assertNotNull($result->client_id);
        $this->assertGreaterThan(0, $result->customer_confidence);
    }

    public function test_extracts_quantity_and_product(): void
    {
        $this->seedData();

        $parser = app(WhatsAppOrderParserService::class);
        $result = $parser->parse('Ahmed need 5 chargers and 10 covers tomorrow');

        $this->assertCount(2, $result->items);
        $this->assertEquals(5, $result->items[0]->quantity);
        $this->assertEquals(10, $result->items[1]->quantity);
    }

    public function test_matches_products_fuzzy(): void
    {
        $this->seedData();

        $parser = app(WhatsAppOrderParserService::class);
        $result = $parser->parse('Order: 3 charger, 5 cover');

        $matched = collect($result->items)->filter(fn ($i) => $i->product_id !== null);
        $this->assertGreaterThanOrEqual(1, $matched->count());
    }

    public function test_extracts_tomorrow_date(): void
    {
        $this->seedData();

        $parser = app(WhatsAppOrderParserService::class);
        $result = $parser->parse('Ahmed need 5 chargers tomorrow');

        $this->assertEquals(now()->addDay()->toDateString(), $result->delivery_date);
        $this->assertNotNull($result->delivery_raw);
    }

    public function test_extracts_next_week_date(): void
    {
        $this->seedData();

        $parser = app(WhatsAppOrderParserService::class);
        $result = $parser->parse('Send 10 covers next week');

        $expected = now()->addWeek()->startOfWeek()->toDateString();
        $this->assertEquals($expected, $result->delivery_date);
    }

    public function test_extracts_explicit_date(): void
    {
        $this->seedData();

        $parser = app(WhatsAppOrderParserService::class);
        $result = $parser->parse('Need 5 chargers by 15 Jan');

        $this->assertNotNull($result->delivery_date);
        $this->assertStringContainsString('01-15', $result->delivery_date);
    }

    public function test_defaults_to_today_when_no_date(): void
    {
        $this->seedData();

        $parser = app(WhatsAppOrderParserService::class);
        $result = $parser->parse('Ahmed need 5 chargers');

        $this->assertEquals(now()->toDateString(), $result->delivery_date);
        $this->assertContains('No delivery date detected. Defaulting to today.', $result->warnings);
    }

    public function test_warns_on_unmatched_customer(): void
    {
        $this->seedData();

        $parser = app(WhatsAppOrderParserService::class);
        $result = $parser->parse('5 chargers and 10 covers');

        $this->assertContains('Could not identify customer name.', $result->warnings);
    }

    public function test_warns_on_unmatched_product(): void
    {
        $this->seedData();

        $parser = app(WhatsAppOrderParserService::class);
        $result = $parser->parse('Ahmed need 5 unicorn horns tomorrow');

        $hasProductWarning = collect($result->warnings)->contains(fn ($w) => str_contains($w, 'not found in catalog'));
        $this->assertTrue($hasProductWarning);
    }

    public function test_handles_multiline_messages(): void
    {
        $this->seedData();

        $parser = app(WhatsAppOrderParserService::class);
        $result = $parser->parse("Ahmed order:\n5 chargers\n10 covers\n3 screen protectors\ntomorrow please");

        $this->assertGreaterThanOrEqual(2, count($result->items));
        $this->assertNotNull($result->delivery_date);
    }

    public function test_handles_empty_message(): void
    {
        $this->act()->postJson('/orders/parse', ['message' => ''])
            ->assertStatus(422);
    }

    // ── API integration tests ──

    public function test_parse_endpoint_returns_structured_result(): void
    {
        $this->seedData();

        $response = $this->act()->postJson('/orders/parse', [
            'message' => 'Ahmed need 5 chargers and 10 covers tomorrow',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'raw_message',
                'customer_name',
                'client_id',
                'customer_confidence',
                'items',
                'delivery_date',
                'warnings',
                'item_count',
            ]);

        $this->assertGreaterThan(0, $response->json('item_count'));
    }

    public function test_confirm_creates_draft_invoice(): void
    {
        $this->seedData();
        $client = Client::where('company_id', $this->company->id)->first();
        $product = Product::where('company_id', $this->company->id)->first();

        $response = $this->act()->postJson('/orders/confirm', [
            'client_id' => $client->id,
            'delivery_date' => now()->addDay()->toDateString(),
            'items' => [
                [
                    'product_id' => $product->id,
                    'description' => $product->name,
                    'quantity' => 5,
                    'unit_price' => $product->unit_price,
                    'vat_rate' => 5,
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.client.id', $client->id);

        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_items', 1);
    }

    public function test_confirm_validates_required_fields(): void
    {
        $this->act()->postJson('/orders/confirm', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['client_id', 'delivery_date', 'items']);
    }

    // ── RBAC ──

    public function test_salesman_can_parse_orders(): void
    {
        $this->seedData();
        $salesman = User::factory()->create(['company_id' => $this->company->id]);
        $salesman->assignRole('salesman');

        $this->actingAs($salesman, 'sanctum')
            ->postJson('/orders/parse', ['message' => 'Ahmed need 5 chargers'])
            ->assertOk();
    }

    public function test_warehouse_manager_cannot_parse_orders(): void
    {
        $wm = User::factory()->create(['company_id' => $this->company->id]);
        $wm->assignRole('warehouse_manager');

        $this->actingAs($wm, 'sanctum')
            ->postJson('/orders/parse', ['message' => 'Ahmed need 5 chargers'])
            ->assertForbidden();
    }
}
