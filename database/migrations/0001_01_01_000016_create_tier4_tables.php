<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Client Portal Tokens ──
        Schema::create('client_portal_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamps();
            $table->index(['token', 'expires_at']);
        });

        // ── Product Categories ──
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'slug']);
        });

        // Add category_id to products
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('product_category_id')->nullable()->after('company_id')->constrained('product_categories')->nullOnDelete();
        });

        // ── Payment Methods ──
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type'); // bank_transfer, cash, cheque, card, online
            $table->text('instructions')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('iban')->nullable();
            $table->string('swift_code')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->index('company_id');
        });

        // ── Invoice Templates ──
        Schema::create('invoice_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('layout')->default('standard'); // standard, modern, minimal
            $table->string('primary_color', 7)->default('#4f46e5');
            $table->string('font')->default('sans-serif');
            $table->boolean('show_logo')->default(true);
            $table->boolean('show_qr')->default(true);
            $table->boolean('show_payment_info')->default(true);
            $table->boolean('bilingual')->default(true);
            $table->text('header_text')->nullable();
            $table->text('footer_text')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // ── Scheduled Reports ──
        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('report_type'); // sales_summary, expense_summary, profit_loss, aging_report, vat_summary
            $table->enum('frequency', ['weekly', 'monthly']);
            $table->string('email_to');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('next_send_at')->nullable();
            $table->timestamps();
            $table->index(['is_active', 'next_send_at']);
        });

        // ── Import Logs ──
        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('imported_by')->constrained('users')->cascadeOnDelete();
            $table->string('type'); // clients, products, invoices
            $table->string('filename');
            $table->integer('total_rows')->default(0);
            $table->integer('success_count')->default(0);
            $table->integer('error_count')->default(0);
            $table->json('errors')->nullable();
            $table->enum('status', ['processing', 'completed', 'failed'])->default('processing');
            $table->timestamps();
        });

        // ── Inventory Adjustments ──
        Schema::create('inventory_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('adjusted_by')->constrained('users')->cascadeOnDelete();
            $table->string('reference')->nullable();
            $table->string('reason'); // stock_count, damage, theft, correction, other
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'applied'])->default('draft');
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'warehouse_id']);
        });

        Schema::create('inventory_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_adjustment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('system_quantity');
            $table->integer('actual_quantity');
            $table->integer('difference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustment_items');
        Schema::dropIfExists('inventory_adjustments');
        Schema::dropIfExists('import_logs');
        Schema::dropIfExists('scheduled_reports');
        Schema::dropIfExists('invoice_templates');
        Schema::dropIfExists('payment_methods');
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_category_id');
        });
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('client_portal_tokens');
    }
};
