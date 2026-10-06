<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            // v1, v2, … per project.
            $table->unsignedSmallInteger('number');
            $table->string('status', 16);
            $table->timestamp('sent_at')->nullable();
            // The client's approval (story 18), kept as a signature record.
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('approved_by_name')->nullable();
            $table->string('approved_ip', 45)->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'number']);
        });

        Schema::create('designs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('review_round_id')->constrained()->cascadeOnDelete();
            $table->string('title', 120);
            $table->unsignedSmallInteger('position')->default(0);
            // 'upload' (private disk) or 'demo' (a file shipped in the repo).
            $table->string('source', 8)->default('upload');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime', 16);
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->unsignedInteger('bytes');
            $table->timestamps();

            $table->index(['review_round_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('designs');
        Schema::dropIfExists('review_rounds');
    }
};
