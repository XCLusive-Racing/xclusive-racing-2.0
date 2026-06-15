<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('championships', function (Blueprint $table) {
            $table->enum('missed_rounds_action', ['none', 'penalise'])->default('none')->after('max_missed_rounds');
            $table->integer('missed_rounds_penalty_points')->nullable()->after('missed_rounds_action');
        });
    }

    public function down(): void
    {
        Schema::table('championships', function (Blueprint $table) {
            $table->dropColumn(['missed_rounds_action', 'missed_rounds_penalty_points']);
        });
    }
};
