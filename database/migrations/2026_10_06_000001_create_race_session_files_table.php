<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// The raw ACC results JSON of each race session (laps, sectors, penalties), which the
// results page builds its detailed stats from. It used to be a file on the server's local
// disk, which doesn't survive a deploy — so the stats almost never showed. Kept in the
// database instead, gzipped (RaceSessionFile).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('race_session_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('race_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('race_number')->default(1);
            $table->longText('content'); // base64 of the gzipped JSON
            $table->timestamps();
            $table->unique(['race_id', 'race_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('race_session_files');
    }
};
