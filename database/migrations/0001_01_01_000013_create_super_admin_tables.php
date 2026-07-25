<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add super admin flag to users
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_super_admin')->default(false)->after('is_active');
        });

        // Subscription plans
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Free, Starter, Professional, Enterprise
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_yearly', 10, 2)->default(0);
            $table->string('currency', 3)->default('AED');
            $table->integer('max_users')->default(1);
            $table->integer('max_invoices_per_month')->default(10);
            $table->integer('max_products')->default(50);
            $table->integer('max_warehouses')->default(1);
            $table->integer('max_api_tokens')->default(0);
            $table->boolean('feature_whatsapp_parser')->default(false);
            $table->boolean('feature_api_access')->default(false);
            $table->boolean('feature_webhooks')->default(false);
            $table->boolean('feature_audit_log')->default(false);
            $table->boolean('feature_pdf_invoices')->default(true);
            $table->boolean('feature_payment_reminders')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Company subscriptions
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->string('billing_cycle')->default('monthly'); // monthly, yearly
            $table->string('status')->default('active'); // active, cancelled, past_due, trialing
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('external_id')->nullable(); // Stripe subscription ID
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });

        // Impersonation audit log
        Schema::create('impersonation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('super_admin_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('action'); // started, ended
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at');

            $table->index('super_admin_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('impersonation_logs');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_super_admin');
        });
    }
};
