<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The report form now identifies the reported driver by selecting a registered
     * participant rather than typing a free-text name, so we can link straight to
     * their account. reported_driver_name / reporter_driver_name stay in place —
     * still auto-filled on create — for display and for the steward system's
     * legacy name-matching fallback on older reports.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->foreignId('reported_user_id')->nullable()->after('reported_driver_name')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropForeign(['reported_user_id']);
            $table->dropColumn('reported_user_id');
        });
    }
};
