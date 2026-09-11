<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Phase 6 (docs/championships/PLAN.md): "post-race time penalties" — before this,
// no automatic position/time recalculation existed anywhere in the app. Stored in
// milliseconds to match total_time's own unit (ACC's raw JSON timing), and additive
// (not a replacement) so multiple penalties on the same result stack correctly.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('race_results', function (Blueprint $table) {
            $table->unsignedInteger('time_penalty_ms')->default(0)->after('total_time');
        });
    }

    public function down(): void
    {
        Schema::table('race_results', function (Blueprint $table) {
            $table->dropColumn('time_penalty_ms');
        });
    }
};
