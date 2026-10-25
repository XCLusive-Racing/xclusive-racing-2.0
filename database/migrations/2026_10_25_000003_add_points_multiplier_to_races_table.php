<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Double (or 1.5× / 3×) points for a chosen championship round (league request
// 2026-10-25): every point scored in the round counts that many times
// (Championship::computeDriverStandings()). 1 = a normal round.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->decimal('points_multiplier', 3, 1)->default(1)->after('xcl_r_multiplier');
        });
    }

    public function down(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->dropColumn('points_multiplier');
        });
    }
};
