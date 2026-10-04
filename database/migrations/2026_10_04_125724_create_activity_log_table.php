<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One append-only audit trail across all modules.
     */
    public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            // Kept when the actor's account is deleted; null for system events.
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->nullableMorphs('subject');
            // The project the entry is about, for filtering and client visibility.
            $table->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('event', 64);
            $table->json('properties')->nullable();
            $table->boolean('visible_to_client')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['workspace_id', 'created_at']);
            $table->index(['project_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};
