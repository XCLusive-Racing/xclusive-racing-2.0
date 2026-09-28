<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// How event times are shown (profile settings): the timezone (null = the browser's
// own, detected on the page) and the clock (24-hour by default, or 12-hour AM/PM).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('timezone', 64)->nullable()->after('country');
            $table->boolean('uses_12_hour_clock')->default(false)->after('timezone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['timezone', 'uses_12_hour_clock']);
        });
    }
};
