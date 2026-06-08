<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->unsignedSmallInteger('practice_duration')->nullable()->after('duration_key');
            $table->unsignedSmallInteger('qualifying_duration')->nullable()->after('practice_duration');
            $table->unsignedSmallInteger('race_duration')->nullable()->after('qualifying_duration');
            $table->string('car_class', 50)->nullable()->after('race_duration');
            $table->string('weather', 20)->nullable()->after('car_class');
            $table->string('time_of_day', 20)->nullable()->after('weather');
        });
    }

    public function down(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->dropColumn(['practice_duration', 'qualifying_duration', 'race_duration', 'car_class', 'weather', 'time_of_day']);
        });
    }
};