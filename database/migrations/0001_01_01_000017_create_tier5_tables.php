<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Client Contacts ──
        Schema::create('client_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('role')->nullable(); // decision_maker, accounts, operations
            $table->boolean('is_primary')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['client_id', 'is_primary']);
        });

        // ── Product Bundles ──
        Schema::create('product_bundles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->text('description')->nullable();
            $table->decimal('bundle_price', 14, 2)->nullable(); // null = sum of items
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('company_id');
        });

        Schema::create('product_bundle_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_bundle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 10, 2)->default(1);
        });

        // ── Custom Fields ──
        Schema::create('custom_field_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('entity_type'); // invoice, client, product
            $table->string('field_name');
            $table->string('field_label');
            $table->string('field_type')->default('text'); // text, number, date, select, boolean
            $table->json('options')->nullable(); // for select type
            $table->boolean('is_required')->default(false);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'entity_type', 'field_name']);
        });

        Schema::create('custom_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('custom_field_definition_id')->constrained()->cascadeOnDelete();
            $table->morphs('entity');
            $table->text('value')->nullable();
            $table->timestamps();
            $table->unique(['custom_field_definition_id', 'entity_type', 'entity_id']);
        });

        // ── Dashboard Widget Config ──
        Schema::create('dashboard_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('widgets'); // [{id, visible, order}]
            $table->timestamps();
            $table->unique('user_id');
        });

        // ── Add line_discount to invoice_items ──
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->decimal('discount', 14, 2)->default(0)->after('vat_rate');
            $table->string('discount_type', 10)->default('fixed')->after('discount'); // fixed, percent
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn(['discount', 'discount_type']);
        });
        Schema::dropIfExists('dashboard_configs');
        Schema::dropIfExists('custom_field_values');
        Schema::dropIfExists('custom_field_definitions');
        Schema::dropIfExists('product_bundle_items');
        Schema::dropIfExists('product_bundles');
        Schema::dropIfExists('client_contacts');
    }
};
