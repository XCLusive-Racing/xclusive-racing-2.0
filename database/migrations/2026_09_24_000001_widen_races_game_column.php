<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// races.game was created as enum('acc','lmu','iracing'), so MySQL refused every ACC PC
// ('ac') race ("Data truncated for column 'game'") -- SQLite in the test suite never
// enforced it, so nothing caught it. A plain string, like game on every other table
// (event_formats, championships, bops, ftp_servers); the app validates the value.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->string('game', 20)->change();
        });
    }

    public function down(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->enum('game', ['acc', 'lmu', 'iracing'])->change();
        });
    }
};
