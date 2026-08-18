<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('legacy_races')->default(0)->after('sr_iracing');
            $table->unsignedInteger('legacy_wins')->default(0)->after('legacy_races');
            $table->unsignedInteger('legacy_podiums')->default(0)->after('legacy_wins');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['legacy_races', 'legacy_wins', 'legacy_podiums']);
        });
    }
};
