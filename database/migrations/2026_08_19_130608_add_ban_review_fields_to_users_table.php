<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Manual-review flag set when a CAIC (Causing an Intentional Collision) penalty is
     * processed — an admin/owner clears it by hand once they've reviewed the account.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('ban_review_flagged_at')->nullable()->after('is_suspended');
            $table->text('ban_review_reason')->nullable()->after('ban_review_flagged_at');
            $table->foreignId('ban_review_report_id')->nullable()->after('ban_review_reason')->constrained('reports')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['ban_review_report_id']);
            $table->dropColumn(['ban_review_flagged_at', 'ban_review_reason', 'ban_review_report_id']);
        });
    }
};
