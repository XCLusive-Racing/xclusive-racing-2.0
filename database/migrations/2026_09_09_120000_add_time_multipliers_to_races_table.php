<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->smallInteger('practice_time_multiplier')->default(1)->after('ambient_temp');
            $table->smallInteger('qualifying_time_multiplier')->default(1)->after('practice_time_multiplier');
            $table->smallInteger('race_time_multiplier')->default(1)->after('qualifying_time_multiplier');
        });
    }

    public function down(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->dropColumn(['practice_time_multiplier', 'qualifying_time_multiplier', 'race_time_multiplier']);
        });
    }
};
