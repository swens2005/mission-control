<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brand_kits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->unique()->constrained()->cascadeOnDelete();
            // Type (story 22): font keys from App\Enums\FontChoice.
            $table->string('heading_font', 24);
            $table->string('body_font', 24);
            $table->unsignedTinyInteger('base_size_px');
            // Thousandths: 1250 is a major third.
            $table->unsignedSmallInteger('scale_ratio');
            $table->unsignedTinyInteger('steps_up');
            $table->unsignedTinyInteger('steps_down');
            // Sharing and approval (story 24).
            $table->timestamp('shared_at')->nullable();
            $table->string('public_token', 40)->nullable()->unique();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('approved_by_name')->nullable();
            $table->string('approved_ip', 45)->nullable();
            $table->timestamps();
        });

        Schema::create('colors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_kit_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->string('role', 8);
            $table->unsignedSmallInteger('position')->default(0);
            // OKLCH as integers (App\Support\Color\OklchColor), and the hex
            // the build uses.
            $table->unsignedSmallInteger('lightness');
            $table->unsignedSmallInteger('chroma');
            $table->unsignedSmallInteger('hue');
            $table->char('hex', 7);
            $table->boolean('gamut_adjusted')->default(false);
            $table->timestamps();

            $table->index(['brand_kit_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colors');
        Schema::dropIfExists('brand_kits');
    }
};
