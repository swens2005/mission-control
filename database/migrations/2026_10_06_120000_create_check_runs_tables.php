<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('check_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('launch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('run_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('run_by_name');
            // The URL as it was when checked; the launch's URL can change later.
            $table->string('url', 2048);
            $table->unsignedTinyInteger('passed')->default(0);
            $table->unsignedTinyInteger('warned')->default(0);
            $table->unsignedTinyInteger('failed')->default(0);
            $table->unsignedTinyInteger('skipped')->default(0);
            $table->unsignedInteger('duration_ms')->default(0);
            $table->timestamps();

            $table->index(['launch_id', 'created_at']);
        });

        Schema::create('check_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('check_run_id')->constrained()->cascadeOnDelete();
            $table->string('check_key', 40);
            $table->string('status', 16);
            $table->string('message', 500);
            $table->json('details')->nullable();
            $table->unsignedTinyInteger('position')->default(0);
        });

        Schema::create('check_waivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('launch_id')->constrained()->cascadeOnDelete();
            $table->string('check_key', 40);
            $table->string('reason', 500);
            $table->foreignId('waived_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('waived_by_name');
            $table->timestamps();

            $table->unique(['launch_id', 'check_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('check_waivers');
        Schema::dropIfExists('check_results');
        Schema::dropIfExists('check_runs');
    }
};
