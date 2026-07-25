<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── ZATCA fields on invoices ──
        Schema::table('invoices', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
            $table->string('zatca_status')->nullable()->after('status'); // pending, reported, cleared, rejected
            $table->text('zatca_qr_tlv')->nullable()->after('zatca_status');
            $table->text('zatca_xml')->nullable()->after('zatca_qr_tlv');
            $table->string('zatca_hash')->nullable()->after('zatca_xml');
            $table->timestamp('zatca_submitted_at')->nullable()->after('zatca_hash');
        });

        // ── Document Numbering Config ──
        Schema::create('document_number_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('document_type'); // invoice, quotation, sales_order, purchase_order, credit_note, delivery_note
            $table->string('prefix')->default('INV');
            $table->string('suffix')->nullable();
            $table->integer('next_number')->default(1);
            $table->integer('padding')->default(6);
            $table->string('separator')->default('-');
            $table->boolean('include_year')->default(false);
            $table->timestamps();
            $table->unique(['company_id', 'document_type']);
        });

        // ── Batch Payments ──
        Schema::create('batch_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
            $table->string('reference')->nullable();
            $table->decimal('total_amount', 14, 2);
            $table->string('method');
            $table->date('payment_date');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'client_id']);
        });

        Schema::create('batch_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_payment_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
        });

        // ── Client credit_hold flag ──
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('credit_hold')->default(false)->after('payment_terms');
        });

        // ── Activity Timeline ──
        Schema::create('activity_timeline', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->morphs('subject');
            $table->string('action'); // created, updated, sent, paid, cancelled, etc.
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_timeline');
        Schema::table('clients', fn (Blueprint $t) => $t->dropColumn('credit_hold'));
        Schema::dropIfExists('batch_payment_allocations');
        Schema::dropIfExists('batch_payments');
        Schema::dropIfExists('document_number_configs');
        Schema::table('invoices', fn (Blueprint $t) => $t->dropColumn(['uuid', 'zatca_status', 'zatca_qr_tlv', 'zatca_xml', 'zatca_hash', 'zatca_submitted_at']));
    }
};
