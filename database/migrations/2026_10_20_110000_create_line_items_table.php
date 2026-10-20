<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The studio's price list (story 26). Proposals copy items, so
        // changing or archiving one never changes a proposal.
        Schema::create('line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('description', 500)->nullable();
            // App\Enums\LineItemKind: fixed or hourly.
            $table->string('kind', 8);
            // Integer cents and hundredths (ADR 0010).
            $table->unsignedInteger('unit_price_cents');
            $table->unsignedInteger('default_quantity');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('line_items');
    }
};
