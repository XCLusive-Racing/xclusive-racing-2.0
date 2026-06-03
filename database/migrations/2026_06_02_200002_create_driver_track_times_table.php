<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_track_times', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->string('xuid_psid')->index();
            $table->string('track');
            $table->string('best_race_lap')->nullable();
            $table->string('best_qualifying_lap')->nullable();
            $table->timestamps();

            $table->unique(['xuid_psid', 'track']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_track_times');
    }
};