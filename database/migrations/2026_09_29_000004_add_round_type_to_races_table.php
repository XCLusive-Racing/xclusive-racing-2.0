<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A championship round's type (Standard / Endurance / Sprint, or one of the
// league's own), shown next to the round. A type a league adds once from the
// round form stays in its dropdown from then on (league_round_types).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->string('round_type', 50)->nullable()->after('round_number');
        });

        Schema::create('league_round_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50);
            $table->timestamps();

            $table->unique(['league_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('league_round_types');

        Schema::table('races', function (Blueprint $table) {
            $table->dropColumn('round_type');
        });
    }
};
