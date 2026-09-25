<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// League feedback (NLRL, 2026-09): a team registering for a championship couldn't
// pick which of its members drive the car — every member was entered into every
// round. driver_ids holds the picked line-up; null (older rows) still means
// "the whole team", so existing registrations keep behaving as before.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('championship_registrations', function (Blueprint $table) {
            $table->json('driver_ids')->nullable()->after('starting_driver_id');
        });
    }

    public function down(): void
    {
        Schema::table('championship_registrations', function (Blueprint $table) {
            $table->dropColumn('driver_ids');
        });
    }
};
