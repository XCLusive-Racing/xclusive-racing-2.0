<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A championship round's official broadcast (2026-10-29): set on Add/Edit Round,
// linked on the championship's Rounds tab, the event page and the event card.
// Null = no official stream.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->string('official_stream_url', 255)->nullable()->after('points_multiplier');
        });
    }

    public function down(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->dropColumn('official_stream_url');
        });
    }
};
