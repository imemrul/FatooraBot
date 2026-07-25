<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Get started with basic invoicing.',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'max_users' => 1,
                'max_invoices_per_month' => 10,
                'max_products' => 25,
                'max_warehouses' => 1,
                'max_api_tokens' => 0,
                'feature_whatsapp_parser' => false,
                'feature_api_access' => false,
                'feature_webhooks' => false,
                'feature_audit_log' => false,
                'feature_pdf_invoices' => true,
                'feature_payment_reminders' => false,
                'sort_order' => 1,
            ],
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'For growing businesses.',
                'price_monthly' => 99,
                'price_yearly' => 990,
                'max_users' => 5,
                'max_invoices_per_month' => 100,
                'max_products' => 200,
                'max_warehouses' => 2,
                'max_api_tokens' => 2,
                'feature_whatsapp_parser' => true,
                'feature_api_access' => false,
                'feature_webhooks' => false,
                'feature_audit_log' => false,
                'feature_pdf_invoices' => true,
                'feature_payment_reminders' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'Full-featured for established businesses.',
                'price_monthly' => 249,
                'price_yearly' => 2490,
                'max_users' => 15,
                'max_invoices_per_month' => 500,
                'max_products' => 1000,
                'max_warehouses' => 5,
                'max_api_tokens' => 10,
                'feature_whatsapp_parser' => true,
                'feature_api_access' => true,
                'feature_webhooks' => true,
                'feature_audit_log' => true,
                'feature_pdf_invoices' => true,
                'feature_payment_reminders' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Unlimited everything. Priority support.',
                'price_monthly' => 499,
                'price_yearly' => 4990,
                'max_users' => 999,
                'max_invoices_per_month' => 99999,
                'max_products' => 99999,
                'max_warehouses' => 99,
                'max_api_tokens' => 50,
                'feature_whatsapp_parser' => true,
                'feature_api_access' => true,
                'feature_webhooks' => true,
                'feature_audit_log' => true,
                'feature_pdf_invoices' => true,
                'feature_payment_reminders' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::firstOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
