<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->unsignedTinyInteger('pitstop_count')->default(0)->after('race_duration');
            $table->unsignedSmallInteger('min_stop_secs')->nullable()->after('pitstop_count');
        });
    }

    public function down(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->dropColumn(['pitstop_count', 'min_stop_secs']);
        });
    }
};
