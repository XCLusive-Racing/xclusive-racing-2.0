<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A supporter's own three-letter in-game abbreviation (ACC's shortName on the leaderboard).
// Everyone else, and a supporter who hasn't picked one, shows as "XCL"
// (AccServerConfigService::entryShortName()).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('short_name', 3)->nullable()->after('stream_url');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('short_name');
        });
    }
};
