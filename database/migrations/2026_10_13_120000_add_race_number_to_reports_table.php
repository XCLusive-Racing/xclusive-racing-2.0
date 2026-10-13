<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Which race of a multi-race round (R1/R2) a race-session incident happened in; null for
// a round with one race, or a qualifying/practice incident.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->unsignedTinyInteger('race_number')->nullable()->after('session_type');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('race_number');
        });
    }
};
