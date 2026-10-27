<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// "Mandatory Tyre Change" for custom races, championship rounds and league session
// formats too (2026-10-27), same as event formats. Off by default, so nothing pushes
// a different config until an admin turns it on.
return new class extends Migration
{
    private const TABLES = ['races', 'session_formats'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->boolean('mandatory_tyre_change')->default(false)->after('mandatory_refuelling');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('mandatory_tyre_change');
            });
        }
    }
};
