<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Tutorial Progress (per user) ──
        Schema::create('tutorial_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('tutorial_key'); // welcome_tour, create_invoice, manage_stock, etc.
            $table->integer('current_step')->default(0);
            $table->boolean('completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'tutorial_key']);
        });

        // ── Help Articles ──
        Schema::create('help_articles', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('category'); // getting_started, invoices, inventory, payments, reports, whatsapp
            $table->text('summary');
            $table->text('content'); // markdown
            $table->string('video_url')->nullable();
            $table->json('tags')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
            $table->index(['category', 'is_published']);
        });

        // ── Add tutorial flags to users ──
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('has_seen_welcome_tour')->default(false)->after('is_super_admin');
            $table->integer('tutorial_score')->default(0)->after('has_seen_welcome_tour');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['has_seen_welcome_tour', 'tutorial_score']));
        Schema::dropIfExists('help_articles');
        Schema::dropIfExists('tutorial_progress');
    }
};
