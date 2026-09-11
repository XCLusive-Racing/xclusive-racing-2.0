<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Extends the existing championships table (XCL's own in-house championships)
// to also carry external leagues' championships, rather than forking a second
// table — the standings/points logic already on this model keeps working for
// both. New rules live in `settings`, not new columns.
//
// Historical note: `league_id` was nullable with null meaning "XCL's own"
// until Phase 2.5 (docs/championships/PLAN.md) gave XCL its own real League
// row and backfilled every null here to it — see
// 2026_09_10_000002_create_xcl_league_and_backfill_tenants.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('championships', function (Blueprint $table) {
            $table->foreignId('league_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('slug')->nullable()->unique()->after('name');
            $table->string('platform')->nullable()->after('game');
            $table->string('visibility')->default('public')->after('status');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            // Granted by an XCL admin only — never settable through the league
            // manager's wizard. approved_by/approved_at record who and when.
            $table->boolean('xcl_rating_enabled')->default(false);
            $table->timestamp('xcl_rating_approved_at')->nullable();
            $table->foreignId('xcl_rating_approved_by')->nullable()->constrained('users')->nullOnDelete();

            $table->json('settings')->nullable();
            $table->unsignedInteger('settings_version')->default(0);

            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('championships', function (Blueprint $table) {
            $table->dropConstrainedForeignId('league_id');
            $table->dropColumn(['slug', 'platform', 'visibility', 'starts_at', 'ends_at']);
            $table->dropConstrainedForeignId('xcl_rating_approved_by');
            $table->dropColumn(['xcl_rating_enabled', 'xcl_rating_approved_at', 'settings', 'settings_version']);
            $table->dropSoftDeletes();
        });
    }
};
