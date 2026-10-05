<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// The driver's Safety Rating right after this race (RatingService::applySrChange()), so the
// results page can show it next to sr_change. Null for results rated before this column
// existed, until their race is recalculated.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('race_results', function (Blueprint $table) {
            $table->decimal('sr_after', 4, 2)->nullable()->after('sr_change');
        });
    }

    public function down(): void
    {
        Schema::table('race_results', function (Blueprint $table) {
            $table->dropColumn('sr_after');
        });
    }
};
