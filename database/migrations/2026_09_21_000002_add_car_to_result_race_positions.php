<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('result_race_positions', function (Blueprint $table) {
            // Drivers sharing a car (insurance / driver swap) share the car number and result.
            $table->string('car')->nullable()->after('points');
            $table->string('car_number', 20)->nullable()->after('car');
        });
    }

    public function down(): void
    {
        Schema::table('result_race_positions', function (Blueprint $table) {
            $table->dropColumn(['car', 'car_number']);
        });
    }
};
