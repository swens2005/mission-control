<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('launches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('url', 2048);
            $table->timestamps();
        });

        Schema::create('checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('launch_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('hint')->nullable();
            $table->string('owner', 16);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamp('checked_at')->nullable();
            $table->foreignId('checked_by_id')->nullable()->constrained('users')->nullOnDelete();
            // A snapshot, so the name survives the user being removed.
            $table->string('checked_by_name')->nullable();
            $table->timestamps();

            $table->index(['launch_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_items');
        Schema::dropIfExists('launches');
    }
};
