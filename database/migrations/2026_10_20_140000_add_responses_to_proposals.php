<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The client's answer to a sent version (story 29): accepted (typed
        // name, tick box, time and IP) or changes requested with a note.
        Schema::table('proposals', function (Blueprint $table) {
            $table->timestamp('accepted_at')->nullable()->after('total_cents');
            $table->foreignId('accepted_by_id')->nullable()->after('accepted_at')->constrained('users')->nullOnDelete();
            $table->string('accepted_by_name')->nullable()->after('accepted_by_id');
            // The name exactly as the client typed it.
            $table->string('accepted_signature', 120)->nullable()->after('accepted_by_name');
            $table->string('accepted_ip', 45)->nullable()->after('accepted_signature');
            $table->timestamp('changes_requested_at')->nullable()->after('accepted_ip');
            $table->string('changes_requested_by_name')->nullable()->after('changes_requested_at');
            $table->text('changes_note')->nullable()->after('changes_requested_by_name');
        });
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('accepted_by_id');
            $table->dropColumn(['accepted_at', 'accepted_by_name', 'accepted_signature', 'accepted_ip', 'changes_requested_at', 'changes_requested_by_name', 'changes_note']);
        });
    }
};
