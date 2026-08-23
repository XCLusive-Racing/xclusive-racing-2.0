<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('race_team_entries', function (Blueprint $table) {
            $table->foreignId('starting_driver_id')->nullable()->after('car_model')
                  ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('race_team_entries', function (Blueprint $table) {
            $table->dropForeign(['starting_driver_id']);
            $table->dropColumn('starting_driver_id');
        });
    }
};
