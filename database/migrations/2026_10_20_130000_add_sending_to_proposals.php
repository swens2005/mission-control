<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sending freezes a version (story 28). Its totals are stored once,
        // so a later change to the maths can't change what the client saw
        // (ADR 0010).
        Schema::table('proposals', function (Blueprint $table) {
            $table->timestamp('sent_at')->nullable()->after('discount_label');
            $table->string('sent_by_name')->nullable()->after('sent_at');
            $table->unsignedBigInteger('subtotal_cents')->nullable()->after('sent_by_name');
            $table->unsignedBigInteger('discount_cents')->nullable()->after('subtotal_cents');
            $table->unsignedBigInteger('vat_cents')->nullable()->after('discount_cents');
            $table->unsignedBigInteger('total_cents')->nullable()->after('vat_cents');
        });

        Schema::table('proposal_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('total_cents')->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('proposal_lines', function (Blueprint $table) {
            $table->dropColumn('total_cents');
        });

        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn(['sent_at', 'sent_by_name', 'subtotal_cents', 'discount_cents', 'vat_cents', 'total_cents']);
        });
    }
};
