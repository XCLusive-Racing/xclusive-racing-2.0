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
        Schema::table('race_results', function (Blueprint $table) {
            $table->boolean('dsq')->default(false)->after('dnf');
            $table->boolean('dc')->default(false)->after('dnf');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('race_results', function (Blueprint $table) {
            $table->dropColumn(['dsq', 'dc']);
        });
    }
};
