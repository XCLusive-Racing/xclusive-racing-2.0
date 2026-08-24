<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->unsignedInteger('driver_stint_time_mins')->nullable()->after('is_endurance');
            $table->unsignedInteger('max_total_driving_time_mins')->nullable()->after('driver_stint_time_mins');
            $table->boolean('mandatory_driver_swap')->default(false)->after('max_total_driving_time_mins');
        });
    }

    public function down(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->dropColumn(['driver_stint_time_mins', 'max_total_driving_time_mins', 'mandatory_driver_swap']);
        });
    }
};
