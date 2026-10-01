<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A driver class can cover a range of XCL ranks (User::ranks() slugs, e.g. Pro =
// silver and up, Am = rookie to bronze) so an entry is put in its class
// automatically at registration instead of by hand. Both null = manual only.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('championship_driver_classes', function (Blueprint $table) {
            $table->string('min_rank', 20)->nullable()->after('max_entries');
            $table->string('max_rank', 20)->nullable()->after('min_rank');
        });
    }

    public function down(): void
    {
        Schema::table('championship_driver_classes', function (Blueprint $table) {
            $table->dropColumn(['min_rank', 'max_rank']);
        });
    }
};
