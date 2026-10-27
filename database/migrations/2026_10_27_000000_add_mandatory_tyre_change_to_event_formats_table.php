<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// "Mandatory Tyre Change" next to Mandatory Refuelling on event formats (2026-10-27).
// Defaults to off, so every format pushes the same config as before.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_formats', function (Blueprint $table) {
            $table->boolean('mandatory_tyre_change')->default(false)->after('mandatory_refuelling');
        });
    }

    public function down(): void
    {
        Schema::table('event_formats', function (Blueprint $table) {
            $table->dropColumn('mandatory_tyre_change');
        });
    }
};
