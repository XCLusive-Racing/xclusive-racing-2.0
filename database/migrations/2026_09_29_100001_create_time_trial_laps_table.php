<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Every Time Trials lap, not just each driver's best, so leaderboards can be rebuilt if
// the aggregation rules change. Times are integer milliseconds, formatted for display only.
// is_personal_best marks the fastest row per driver, track and car (the leaderboard row);
// source_key makes the import idempotent.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_trial_laps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('platform_identifier', 64);
            $table->enum('platform', ['xbox', 'playstation', 'pc']);
            $table->string('driver_name');
            $table->string('track', 64);
            $table->unsignedSmallInteger('car_id');
            $table->string('car_class', 16);
            $table->unsignedInteger('lap_time_ms');
            $table->unsignedInteger('sector1_ms')->nullable();
            $table->unsignedInteger('sector2_ms')->nullable();
            $table->unsignedInteger('sector3_ms')->nullable();
            $table->string('game_patch', 32)->nullable();
            $table->unsignedInteger('source_event')->nullable();
            $table->unsignedInteger('laps_driven')->nullable();
            $table->enum('source', ['import', 'server']);
            $table->boolean('is_personal_best')->default(false);
            $table->char('source_key', 40)->unique();
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();

            $table->index(['track', 'platform', 'car_id', 'lap_time_ms']);
            $table->index(['track', 'platform', 'is_personal_best', 'lap_time_ms']);
            $table->index('platform_identifier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_trial_laps');
    }
};
