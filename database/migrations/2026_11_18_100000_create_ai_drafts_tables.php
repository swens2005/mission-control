<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The proposal assistant's ledger (story 42, ADR 0014): one row per call
 * to the Claude API, kept when a sandbox is pruned so the month's spend
 * stays right, and one row per month whose cap email went out.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_sandbox');
            $table->unsignedInteger('input_tokens');
            $table->unsignedInteger('output_tokens');
            // Millionths of a US dollar; whole numbers only (ADR 0014).
            $table->unsignedBigInteger('cost_micro_usd');
            $table->unsignedSmallInteger('lines');
            $table->timestamp('created_at')->index();
        });

        Schema::create('ai_cap_notices', function (Blueprint $table) {
            $table->char('month', 7)->primary();
            $table->timestamp('notified_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_cap_notices');
        Schema::dropIfExists('ai_drafts');
    }
};
