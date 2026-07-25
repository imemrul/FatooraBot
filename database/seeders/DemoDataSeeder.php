<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Company;
use App\Models\InventoryLevel;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\PaymentReminder;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    private Company $company;
    private User $admin;
    private array $products = [];
    private array $clients = [];
    private array $warehouses = [];

    public function run(): void
    {
        $this->createCompany();
        $this->createUsers();
        $this->createClients();
        $this->createProducts();
        $this->createWarehouses();
        $this->createInventory();
        $this->createInvoices();
        $this->createSalesOrders();
    }

    private function createCompany(): void
    {
        $this->company = Company::create([
            'name' => 'FatooraBot Trading LLC',
            'email' => 'admin@fatoorabot.ae',
            'phone' => '+971 4 123 4567',
            'trade_license_number' => 'TL-987654',
            'tax_registration_number' => '100123456789003',
            'address' => 'Office 1205, Business Bay Tower',
            'city' => 'Dubai',
            'country' => 'AE',
            'currency' => 'AED',
            'is_active' => true,
            'onboarded_at' => now()->subMonths(6),
        ]);
    }

    private function createUsers(): void
    {
        $this->admin = User::create([
            'company_id' => $this->company->id,
            'name' => 'Khalid Al Maktoum',
            'email' => 'admin@fatoorabot.ae',
            'phone' => '+971 50 111 2222',
            'password' => Hash::make('admin123'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $this->admin->assignRole('owner');

        $accountant = User::create([
            'company_id' => $this->company->id,
            'name' => 'Fatima Al Zahra',
            'email' => 'fatima@fatoorabot.ae',
            'phone' => '+971 50 222 3333',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $accountant->assignRole('accountant');

        $warehouse = User::create([
            'company_id' => $this->company->id,
            'name' => 'Omar Hassan',
            'email' => 'omar@fatoorabot.ae',
            'phone' => '+971 50 333 4444',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $warehouse->assignRole('warehouse_manager');

        $salesman = User::create([
            'company_id' => $this->company->id,
            'name' => 'Ahmed Rashid',
            'email' => 'ahmed@fatoorabot.ae',
            'phone' => '+971 50 444 5555',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $salesman->assignRole('salesman');
    }

    private function createClients(): void
    {
        $data = [
            ['name' => 'Al Futtaim Electronics', 'contact_person' => 'Mohammed Al Futtaim', 'email' => 'mohammed@alfuttaim.ae', 'phone' => '+971 4 555 0001', 'tax_registration_number' => '100000000000001', 'credit_limit' => 100000, 'payment_terms' => 30, 'city' => 'Dubai'],
            ['name' => 'Sharaf DG', 'contact_person' => 'Sara Khan', 'email' => 'sara@sharafdg.com', 'phone' => '+971 4 555 0002', 'tax_registration_number' => '100000000000002', 'credit_limit' => 75000, 'payment_terms' => 45, 'city' => 'Dubai'],
            ['name' => 'Jumbo Electronics', 'contact_person' => 'Ravi Patel', 'email' => 'ravi@jumbo.ae', 'phone' => '+971 4 555 0003', 'tax_registration_number' => '100000000000003', 'credit_limit' => 50000, 'payment_terms' => 30, 'city' => 'Abu Dhabi'],
            ['name' => 'Emax Trading', 'contact_person' => 'Ali Hassan', 'email' => 'ali@emax.ae', 'phone' => '+971 6 555 0004', 'tax_registration_number' => '100000000000004', 'credit_limit' => 60000, 'payment_terms' => 15, 'city' => 'Sharjah'],
            ['name' => 'Carrefour UAE', 'contact_person' => 'Layla Mahmoud', 'email' => 'layla@carrefour.ae', 'phone' => '+971 4 555 0005', 'tax_registration_number' => '100000000000005', 'credit_limit' => 200000, 'payment_terms' => 60, 'city' => 'Dubai'],
            ['name' => 'Lulu Hypermarket', 'contact_person' => 'Yusuf Ibrahim', 'email' => 'yusuf@lulu.ae', 'phone' => '+971 2 555 0006', 'tax_registration_number' => '100000000000006', 'credit_limit' => 150000, 'payment_terms' => 45, 'city' => 'Abu Dhabi'],
            ['name' => 'Dragon Mart Supplies', 'contact_person' => 'Wei Chen', 'email' => 'wei@dragonmart.ae', 'phone' => '+971 4 555 0007', 'credit_limit' => 25000, 'payment_terms' => 15, 'city' => 'Dubai'],
            ['name' => 'Ajman Free Zone Trading', 'contact_person' => 'Nasser Al Suwaidi', 'email' => 'nasser@afzt.ae', 'phone' => '+971 6 555 0008', 'tax_registration_number' => '100000000000008', 'credit_limit' => 40000, 'payment_terms' => 30, 'city' => 'Ajman'],
        ];

        foreach ($data as $d) {
            $this->clients[] = Client::create(array_merge($d, [
                'company_id' => $this->company->id,
                'address' => fake()->address(),
                'country' => 'AE',
            ]));
        }
    }

    private function createProducts(): void
    {
        $data = [
            ['sku' => 'USB-C-65W', 'barcode' => '6291041500001', 'name' => 'USB-C Charger 65W', 'unit_price' => 89.00, 'cost_price' => 42.00, 'low_stock_threshold' => 50],
            ['sku' => 'USB-C-30W', 'barcode' => '6291041500002', 'name' => 'USB-C Charger 30W', 'unit_price' => 49.00, 'cost_price' => 22.00, 'low_stock_threshold' => 100],
            ['sku' => 'HDMI-2M', 'barcode' => '6291041500003', 'name' => 'HDMI Cable 2M 4K', 'unit_price' => 35.00, 'cost_price' => 12.00, 'low_stock_threshold' => 80],
            ['sku' => 'CASE-IP15', 'barcode' => '6291041500004', 'name' => 'iPhone 15 Silicone Case', 'unit_price' => 29.00, 'cost_price' => 8.00, 'low_stock_threshold' => 200],
            ['sku' => 'CASE-S24', 'barcode' => '6291041500005', 'name' => 'Samsung S24 Clear Case', 'unit_price' => 25.00, 'cost_price' => 7.00, 'low_stock_threshold' => 200],
            ['sku' => 'SPR-IP15', 'barcode' => '6291041500006', 'name' => 'iPhone 15 Screen Protector', 'unit_price' => 19.00, 'cost_price' => 3.50, 'low_stock_threshold' => 300],
            ['sku' => 'SPR-S24', 'barcode' => '6291041500007', 'name' => 'Samsung S24 Screen Protector', 'unit_price' => 15.00, 'cost_price' => 3.00, 'low_stock_threshold' => 300],
            ['sku' => 'PB-20K', 'barcode' => '6291041500008', 'name' => 'Power Bank 20000mAh', 'unit_price' => 129.00, 'cost_price' => 55.00, 'low_stock_threshold' => 30],
            ['sku' => 'PB-10K', 'barcode' => '6291041500009', 'name' => 'Power Bank 10000mAh', 'unit_price' => 79.00, 'cost_price' => 32.00, 'low_stock_threshold' => 50],
            ['sku' => 'EAR-BT', 'barcode' => '6291041500010', 'name' => 'Bluetooth Earbuds Pro', 'unit_price' => 159.00, 'cost_price' => 65.00, 'low_stock_threshold' => 40],
            ['sku' => 'SPKR-BT', 'barcode' => '6291041500011', 'name' => 'Bluetooth Speaker Mini', 'unit_price' => 99.00, 'cost_price' => 38.00, 'low_stock_threshold' => 25],
            ['sku' => 'MOUSE-W', 'barcode' => '6291041500012', 'name' => 'Wireless Mouse', 'unit_price' => 45.00, 'cost_price' => 15.00, 'low_stock_threshold' => 60],
            ['sku' => 'KB-BT', 'barcode' => '6291041500013', 'name' => 'Bluetooth Keyboard', 'unit_price' => 119.00, 'cost_price' => 48.00, 'low_stock_threshold' => 20],
            ['sku' => 'STAND-LP', 'barcode' => '6291041500014', 'name' => 'Laptop Stand Aluminum', 'unit_price' => 149.00, 'cost_price' => 58.00, 'low_stock_threshold' => 15],
            ['sku' => 'HUB-USB', 'barcode' => '6291041500015', 'name' => 'USB-C Hub 7-in-1', 'unit_price' => 179.00, 'cost_price' => 72.00, 'low_stock_threshold' => 25],
        ];

        foreach ($data as $d) {
            $this->products[] = Product::create(array_merge($d, [
                'company_id' => $this->company->id,
                'vat_rate' => 5.00,
                'unit' => 'piece',
                'is_active' => true,
            ]));
        }
    }

    private function createWarehouses(): void
    {
        $this->warehouses[] = Warehouse::create([
            'company_id' => $this->company->id,
            'name' => 'Dubai Main Warehouse',
            'location' => 'Al Quoz Industrial Area 3, Dubai',
            'is_active' => true,
        ]);

        $this->warehouses[] = Warehouse::create([
            'company_id' => $this->company->id,
            'name' => 'Sharjah Branch Store',
            'location' => 'Industrial Area 12, Sharjah',
            'is_active' => true,
        ]);
    }

    private function createInventory(): void
    {
        $stockLevels = [500, 800, 600, 1500, 1200, 2000, 1800, 200, 350, 180, 120, 400, 80, 45, 100];

        foreach ($this->products as $i => $product) {
            $mainQty = (int) ($stockLevels[$i] * 0.7);
            $branchQty = $stockLevels[$i] - $mainQty;

            InventoryLevel::create([
                'company_id' => $this->company->id,
                'product_id' => $product->id,
                'warehouse_id' => $this->warehouses[0]->id,
                'quantity' => $mainQty,
            ]);

            InventoryLevel::create([
                'company_id' => $this->company->id,
                'product_id' => $product->id,
                'warehouse_id' => $this->warehouses[1]->id,
                'quantity' => $branchQty,
            ]);

            StockMovement::create([
                'company_id' => $this->company->id,
                'product_id' => $product->id,
                'warehouse_id' => $this->warehouses[0]->id,
                'created_by' => $this->admin->id,
                'type' => 'stock_in',
                'quantity' => $mainQty,
                'reference' => 'INIT-' . $product->sku,
                'notes' => 'Initial stock load',
            ]);

            StockMovement::create([
                'company_id' => $this->company->id,
                'product_id' => $product->id,
                'warehouse_id' => $this->warehouses[1]->id,
                'created_by' => $this->admin->id,
                'type' => 'stock_in',
                'quantity' => $branchQty,
                'reference' => 'INIT-' . $product->sku,
                'notes' => 'Initial stock load - branch',
            ]);
        }

        // Make a few products low/out of stock for dashboard alerts
        InventoryLevel::where('product_id', $this->products[13]->id)->update(['quantity' => 3]); // Laptop Stand - low
        InventoryLevel::where('product_id', $this->products[12]->id)
            ->where('warehouse_id', $this->warehouses[1]->id)
            ->update(['quantity' => 0]); // Keyboard - out at branch
    }

    private function createInvoices(): void
    {
        $invoiceData = [
            // Paid invoices (past months)
            ['client' => 0, 'days_ago' => 90, 'due_ago' => 60, 'status' => 'paid', 'items' => [[0, 50], [3, 200]], 'pay_full' => true],
            ['client' => 1, 'days_ago' => 75, 'due_ago' => 45, 'status' => 'paid', 'items' => [[1, 100], [5, 500]], 'pay_full' => true],
            ['client' => 2, 'days_ago' => 60, 'due_ago' => 30, 'status' => 'paid', 'items' => [[7, 30], [9, 20]], 'pay_full' => true],
            ['client' => 4, 'days_ago' => 50, 'due_ago' => 20, 'status' => 'paid', 'items' => [[2, 300], [11, 150]], 'pay_full' => true],
            ['client' => 5, 'days_ago' => 45, 'due_ago' => 15, 'status' => 'paid', 'items' => [[4, 400], [6, 600]], 'pay_full' => true],
            ['client' => 0, 'days_ago' => 40, 'due_ago' => 10, 'status' => 'paid', 'items' => [[8, 80], [10, 40]], 'pay_full' => true],

            // Partially paid
            ['client' => 1, 'days_ago' => 30, 'due_ago' => -5, 'status' => 'sent', 'items' => [[0, 100], [7, 50]], 'pay_partial' => 0.6],
            ['client' => 3, 'days_ago' => 25, 'due_ago' => -3, 'status' => 'sent', 'items' => [[9, 25], [10, 30]], 'pay_partial' => 0.4],

            // Overdue
            ['client' => 6, 'days_ago' => 35, 'due_ago' => -10, 'status' => 'overdue', 'items' => [[1, 200], [3, 500]]],
            ['client' => 7, 'days_ago' => 28, 'due_ago' => -7, 'status' => 'overdue', 'items' => [[14, 15], [12, 10]]],
            ['client' => 2, 'days_ago' => 20, 'due_ago' => -2, 'status' => 'overdue', 'items' => [[5, 300]]],

            // Sent (due soon / due today)
            ['client' => 0, 'days_ago' => 10, 'due_ago' => 0, 'status' => 'sent', 'items' => [[0, 75], [1, 150], [2, 100]]],
            ['client' => 4, 'days_ago' => 7, 'due_ago' => 3, 'status' => 'sent', 'items' => [[8, 60], [9, 40]]],
            ['client' => 5, 'days_ago' => 5, 'due_ago' => 10, 'status' => 'sent', 'items' => [[3, 800], [4, 600], [5, 1000]]],
            ['client' => 3, 'days_ago' => 3, 'due_ago' => 12, 'status' => 'sent', 'items' => [[13, 8], [14, 12]]],

            // Drafts
            ['client' => 1, 'days_ago' => 1, 'due_ago' => 29, 'status' => 'draft', 'items' => [[10, 50], [11, 80]]],
            ['client' => 6, 'days_ago' => 0, 'due_ago' => 30, 'status' => 'draft', 'items' => [[7, 20]]],

            // Today's invoices
            ['client' => 0, 'days_ago' => 0, 'due_ago' => 30, 'status' => 'sent', 'items' => [[0, 30], [9, 15]]],
            ['client' => 2, 'days_ago' => 0, 'due_ago' => 30, 'status' => 'sent', 'items' => [[1, 50], [5, 200]]],
            ['client' => 4, 'days_ago' => 0, 'due_ago' => 45, 'status' => 'sent', 'items' => [[14, 20], [8, 25]]],
        ];

        $seq = 1;
        foreach ($invoiceData as $d) {
            $client = $this->clients[$d['client']];
            $issueDate = now()->subDays($d['days_ago']);
            $dueDate = $d['due_ago'] >= 0
                ? now()->addDays($d['due_ago'])
                : now()->subDays(abs($d['due_ago']));

            $invoice = Invoice::create([
                'company_id' => $this->company->id,
                'client_id' => $client->id,
                'created_by' => $this->admin->id,
                'invoice_number' => 'INV-0001-' . str_pad($seq++, 6, '0', STR_PAD_LEFT),
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'currency' => 'AED',
                'status' => $d['status'],
                'discount' => 0,
            ]);

            foreach ($d['items'] as [$prodIdx, $qty]) {
                $product = $this->products[$prodIdx];
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $product->id,
                    'description' => $product->name,
                    'quantity' => $qty,
                    'unit_price' => $product->unit_price,
                    'vat_rate' => 5.00,
                ]);
            }

            $invoice->recalculateTotals();

            if (!empty($d['pay_full'])) {
                $payment = InvoicePayment::create([
                    'company_id' => $this->company->id,
                    'invoice_id' => $invoice->id,
                    'recorded_by' => $this->admin->id,
                    'amount' => $invoice->total,
                    'method' => collect(['bank_transfer', 'cash', 'cheque', 'card'])->random(),
                    'reference' => 'PAY-' . fake()->numerify('######'),
                    'payment_date' => $dueDate->copy()->subDays(rand(1, 5)),
                ]);
                $invoice->syncPaidAmount();
            }

            if (!empty($d['pay_partial'])) {
                $partialAmount = round((float) $invoice->total * $d['pay_partial'], 2);
                InvoicePayment::create([
                    'company_id' => $this->company->id,
                    'invoice_id' => $invoice->id,
                    'recorded_by' => $this->admin->id,
                    'amount' => $partialAmount,
                    'method' => 'bank_transfer',
                    'reference' => 'PAY-' . fake()->numerify('######'),
                    'payment_date' => $issueDate->copy()->addDays(rand(5, 15)),
                ]);
                $invoice->syncPaidAmount();
            }

            // Add reminders for overdue invoices
            if ($d['status'] === 'overdue') {
                PaymentReminder::create([
                    'company_id' => $this->company->id,
                    'invoice_id' => $invoice->id,
                    'sent_by' => $this->admin->id,
                    'channel' => 'email',
                    'recipient' => $client->email,
                    'status' => 'sent',
                    'message_preview' => "Payment reminder for {$invoice->invoice_number}",
                    'sent_at' => now()->subDays(rand(1, 3)),
                ]);
            }
        }
    }

    private function createSalesOrders(): void
    {
        $orders = [
            ['client' => 0, 'status' => 'delivered', 'items' => [[0, 25], [1, 50]]],
            ['client' => 1, 'status' => 'confirmed', 'items' => [[3, 100], [5, 200]]],
            ['client' => 4, 'status' => 'confirmed', 'items' => [[7, 15], [8, 30]]],
            ['client' => 2, 'status' => 'draft', 'items' => [[9, 10], [10, 20]]],
            ['client' => 5, 'status' => 'draft', 'items' => [[14, 8], [13, 5]]],
        ];

        $seq = 1;
        foreach ($orders as $d) {
            $order = SalesOrder::create([
                'company_id' => $this->company->id,
                'client_id' => $this->clients[$d['client']]->id,
                'created_by' => $this->admin->id,
                'warehouse_id' => $this->warehouses[0]->id,
                'order_number' => 'SO-0001-' . str_pad($seq++, 6, '0', STR_PAD_LEFT),
                'order_date' => now()->subDays(rand(1, 10)),
                'delivery_date' => now()->addDays(rand(3, 14)),
                'currency' => 'AED',
                'status' => $d['status'],
            ]);

            foreach ($d['items'] as [$prodIdx, $qty]) {
                $product = $this->products[$prodIdx];
                SalesOrderItem::create([
                    'sales_order_id' => $order->id,
                    'product_id' => $product->id,
                    'description' => $product->name,
                    'quantity' => $qty,
                    'unit_price' => $product->unit_price,
                    'vat_rate' => 5.00,
                ]);
            }

            $order->recalculateTotals();
        }
    }
}
