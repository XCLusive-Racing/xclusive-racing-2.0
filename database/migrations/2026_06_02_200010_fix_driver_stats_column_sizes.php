<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('driver_stats', function (Blueprint $table) {
            $table->bigInteger('total_race_positions')->default(0)->change();
            $table->bigInteger('total_qualy_positions')->default(0)->change();
            $table->bigInteger('total_spectated')->default(0)->change();
            $table->bigInteger('positions_gained')->default(0)->change();
            $table->bigInteger('total_races')->default(0)->change();
            $table->bigInteger('total_races_2024')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('driver_stats', function (Blueprint $table) {
            $table->unsignedInteger('total_race_positions')->default(0)->change();
            $table->unsignedInteger('total_qualy_positions')->default(0)->change();
            $table->unsignedInteger('total_spectated')->default(0)->change();
            $table->integer('positions_gained')->default(0)->change();
            $table->unsignedInteger('total_races')->default(0)->change();
            $table->unsignedInteger('total_races_2024')->default(0)->change();
        });
    }
};