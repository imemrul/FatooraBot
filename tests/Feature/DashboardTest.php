<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\InventoryLevel;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
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

    private function createInvoice(float $total, string $status = 'sent', ?string $issueDate = null, ?string $dueDate = null): Invoice
    {
        return Invoice::create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
            'invoice_number' => 'INV-' . fake()->unique()->numerify('######'),
            'issue_date' => $issueDate ?? now()->toDateString(),
            'due_date' => $dueDate ?? now()->addDays(30)->toDateString(),
            'total' => $total,
            'paid_amount' => 0,
            'status' => $status,
        ]);
    }

    public function test_dashboard_returns_all_sections(): void
    {
        $response = $this->act()->getJson('/api/dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'stats' => ['daily_sales', 'monthly_revenue', 'monthly_collected', 'total_outstanding', 'invoice_count', 'client_count'],
                'revenue_trend',
                'collection_trend',
                'top_customers',
                'low_stock',
                'reminders' => ['overdue', 'due_today', 'due_soon', 'recent_reminders'],
            ]);
    }

    public function test_daily_sales_stat(): void
    {
        $this->createInvoice(5000);
        $this->createInvoice(3000);
        $this->createInvoice(1000, 'draft'); // excluded

        $response = $this->act()->getJson('/api/dashboard');

        $this->assertEquals(8000.0, $response->json('stats.daily_sales'));
    }

    public function test_monthly_revenue_stat(): void
    {
        $this->createInvoice(10000);
        $this->createInvoice(5000, 'sent', now()->subMonth()->toDateString()); // different month

        $response = $this->act()->getJson('/api/dashboard');

        $this->assertEquals(10000.0, $response->json('stats.monthly_revenue'));
    }

    public function test_monthly_collected_stat(): void
    {
        $invoice = $this->createInvoice(5000);
        InvoicePayment::create([
            'company_id' => $this->company->id,
            'invoice_id' => $invoice->id,
            'recorded_by' => $this->user->id,
            'amount' => 3000,
            'method' => 'bank_transfer',
            'payment_date' => now()->toDateString(),
        ]);

        $response = $this->act()->getJson('/api/dashboard');

        $this->assertEquals(3000.0, $response->json('stats.monthly_collected'));
    }

    public function test_outstanding_stat(): void
    {
        $this->createInvoice(5000);
        $inv2 = $this->createInvoice(3000);
        $inv2->update(['paid_amount' => 1000]);

        $response = $this->act()->getJson('/api/dashboard');

        $this->assertEquals(7000.0, $response->json('stats.total_outstanding'));
    }

    public function test_revenue_trend_has_12_months(): void
    {
        $response = $this->act()->getJson('/api/dashboard');

        $this->assertCount(12, $response->json('revenue_trend'));
    }

    public function test_collection_trend_has_12_months(): void
    {
        $response = $this->act()->getJson('/api/dashboard');

        $this->assertCount(12, $response->json('collection_trend'));
    }

    public function test_top_customers(): void
    {
        $this->createInvoice(10000);

        $client2 = Client::factory()->create(['company_id' => $this->company->id]);
        Invoice::create([
            'company_id' => $this->company->id,
            'client_id' => $client2->id,
            'created_by' => $this->user->id,
            'invoice_number' => 'INV-TOP-002',
            'issue_date' => now(),
            'due_date' => now()->addDays(30),
            'total' => 20000,
            'status' => 'sent',
        ]);

        $response = $this->act()->getJson('/api/dashboard');

        $top = $response->json('top_customers');
        $this->assertGreaterThanOrEqual(2, count($top));
        $this->assertEquals($client2->id, $top[0]['id']); // highest first
    }

    public function test_low_stock_products(): void
    {
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
            'low_stock_threshold' => 20,
        ]);
        $warehouse = Warehouse::factory()->create(['company_id' => $this->company->id]);
        InventoryLevel::create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 5,
        ]);

        $response = $this->act()->getJson('/api/dashboard');

        $lowStock = $response->json('low_stock');
        $this->assertCount(1, $lowStock);
        $this->assertEquals('low', $lowStock[0]['status']);
    }

    public function test_overdue_in_reminders(): void
    {
        $this->createInvoice(5000, 'sent', now()->subDays(30)->toDateString(), now()->subDays(5)->toDateString());

        $response = $this->act()->getJson('/api/dashboard');

        $this->assertEquals(1, $response->json('reminders.overdue.count'));
    }

    public function test_tenant_isolation(): void
    {
        $this->createInvoice(10000);

        $other = Company::factory()->create();
        $otherUser = User::factory()->create(['company_id' => $other->id]);
        $otherUser->assignRole('owner');

        $response = $this->actingAs($otherUser, 'sanctum')->getJson('/api/dashboard');

        $this->assertEquals(0, $response->json('stats.daily_sales'));
        $this->assertEquals(0, $response->json('stats.invoice_count'));
    }
}
