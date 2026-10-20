<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Scope proposals (story 27). Each version is its own row; money in
        // integer cents (ADR 0010).
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            // v1, v2, …
            $table->unsignedSmallInteger('number');
            $table->string('title', 120);
            $table->text('introduction')->nullable();
            // App\Enums\DiscountType; the value is basis points or cents.
            $table->string('discount_type', 10)->default('none');
            $table->unsignedInteger('discount_value')->default(0);
            $table->string('discount_label', 60)->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'number']);
        });

        Schema::create('proposal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            // Where it came from; the line keeps its own copy of everything.
            $table->foreignId('line_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 80);
            $table->string('description', 500)->nullable();
            $table->string('kind', 8);
            $table->unsignedInteger('unit_price_cents');
            // Hundredths: 1.5 hours is 150.
            $table->unsignedInteger('quantity');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['proposal_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_lines');
        Schema::dropIfExists('proposals');
    }
};
