<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intakes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->unique()->constrained()->cascadeOnDelete();
            // The client's answers; all optional until submitted.
            $table->text('goals')->nullable();
            // A list of page names.
            $table->json('pages')->nullable();
            // Keys from App\Enums\IntakeFeature.
            $table->json('features')->nullable();
            $table->string('features_other', 200)->nullable();
            // A bracket from App\Enums\IntakeBudget, not an amount of money.
            $table->string('budget', 16)->nullable();
            $table->date('deadline_on')->nullable();
            $table->string('deadline_reason', 120)->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('submitted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('submitted_by_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intakes');
    }
};
