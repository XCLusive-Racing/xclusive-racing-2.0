<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('race_team_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('race_id')->constrained()->cascadeOnDelete();
            $table->foreignId('racing_team_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('car_number')->nullable();
            $table->string('car_model')->nullable();
            // Also added by add_starting_driver_to_race_team_entries_table.php, whose
            // timestamp predates this table's creation — included here directly so a
            // fresh install ends up complete regardless of which migration ran first.
            $table->foreignId('starting_driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['race_id', 'car_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('race_team_entries');
    }
};