<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('design_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            // Snapshots, so the comment survives the user being removed.
            $table->string('author_name');
            $table->string('author_role', 8);
            // Hundredths of a percent of the image: 0 to 10 000.
            $table->unsignedSmallInteger('x');
            $table->unsignedSmallInteger('y');
            $table->text('body');
            // Story 18.
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('resolved_by_name')->nullable();
            $table->timestamps();

            $table->index(['design_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
