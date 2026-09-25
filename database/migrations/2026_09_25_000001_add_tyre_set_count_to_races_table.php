<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// League feedback (NLRL, 2026-09): limited tyre sets. Pushed as ACC's
// eventRules.json tyreSetCount; null leaves the game default (50) alone.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->unsignedTinyInteger('tyre_set_count')->nullable()->after('min_stop_secs');
        });
    }

    public function down(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->dropColumn('tyre_set_count');
        });
    }
};
