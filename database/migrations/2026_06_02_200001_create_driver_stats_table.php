<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->string('xuid_psid')->index();
            $table->unsignedInteger('wins')->default(0);
            $table->unsignedInteger('seconds')->default(0);
            $table->unsignedInteger('thirds')->default(0);
            $table->unsignedInteger('total_races')->default(0);
            $table->unsignedInteger('podiums')->default(0);
            $table->unsignedInteger('top5s')->default(0);
            $table->unsignedInteger('top10s')->default(0);
            $table->unsignedInteger('fastest_race_laps')->default(0);
            $table->unsignedInteger('fastest_qualifying_laps')->default(0);
            $table->unsignedInteger('total_spectated')->default(0);
            $table->unsignedInteger('total_race_positions')->default(0);
            $table->unsignedInteger('total_qualy_positions')->default(0);
            $table->integer('positions_gained')->default(0);
            $table->unsignedInteger('total_races_2024')->default(0);
            // Penalty counts from DRIVERSTATS (cols 109–119)
            $table->unsignedSmallInteger('penalty_pit_speeding')->default(0);
            $table->unsignedSmallInteger('penalty_wrong_way')->default(0);
            $table->unsignedSmallInteger('penalty_cutting')->default(0);
            $table->unsignedSmallInteger('penalty_trolling')->default(0);
            $table->unsignedSmallInteger('penalty_start_speeding')->default(0);
            $table->unsignedSmallInteger('penalty_out_of_start_pos')->default(0);
            $table->unsignedSmallInteger('penalty_stop_go_30')->default(0);
            $table->unsignedSmallInteger('penalty_disqualified')->default(0);
            $table->unsignedSmallInteger('penalty_drive_through')->default(0);
            $table->unsignedSmallInteger('penalty_post_race_time')->default(0);
            $table->unsignedSmallInteger('penalty_other')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_stats');
    }
};