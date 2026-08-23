<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('racing_teams', function (Blueprint $table) {
            $table->string('logo')->nullable()->after('tag');
        });
    }

    public function down(): void
    {
        Schema::table('racing_teams', function (Blueprint $table) {
            $table->dropColumn('logo');
        });
    }
};
