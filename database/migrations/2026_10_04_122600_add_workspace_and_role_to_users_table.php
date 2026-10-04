<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('workspace_id')->after('id')->constrained()->cascadeOnDelete();
            // Least privilege by default; admins are always created explicitly.
            $table->string('role', 16)->default('client')->after('workspace_id');
            $table->foreignId('organization_id')->nullable()->after('role')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
            $table->dropColumn('role');
            $table->dropConstrainedForeignId('workspace_id');
        });
    }
};
