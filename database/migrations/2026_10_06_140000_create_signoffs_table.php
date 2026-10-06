<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signoffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('launch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role', 16);
            // What the person typed as their signature, and where from.
            $table->string('name_typed', 120);
            $table->string('ip', 45)->nullable();
            $table->timestamp('signed_at');
            // Kept for the record when the board stops being clear.
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason')->nullable();
            $table->timestamps();

            $table->index(['launch_id', 'voided_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signoffs');
    }
};
