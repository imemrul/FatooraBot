<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── WhatsApp Linked Phones (maps phone → user + company) ──
        Schema::create('whatsapp_phones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('phone', 20)->unique(); // E.164 format
            $table->boolean('is_active')->default(true);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index('phone');
        });

        // ── Conversation State (for multi-step flows) ──
        Schema::create('whatsapp_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->index();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('state')->default('idle'); // idle, awaiting_confirm, awaiting_client, awaiting_product, etc.
            $table->string('intent')->nullable(); // create_invoice, record_payment, etc.
            $table->json('context')->nullable(); // temp data for multi-step
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->unique('phone');
        });

        // ── Message Log ──
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('phone', 20);
            $table->enum('direction', ['inbound', 'outbound']);
            $table->string('wa_message_id')->nullable();
            $table->text('body')->nullable();
            $table->string('message_type')->default('text'); // text, interactive, document, image
            $table->json('payload')->nullable();
            $table->string('status')->default('sent'); // sent, delivered, read, failed
            $table->timestamps();
            $table->index(['phone', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
        Schema::dropIfExists('whatsapp_conversations');
        Schema::dropIfExists('whatsapp_phones');
    }
};
