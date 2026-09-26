<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// settings.requirements.manual_approval_required was shown on the public page
// ("Manually reviewed") but never enforced. approved_at null = waiting for the
// league to approve; every existing registration was auto-accepted, so it's
// backfilled as approved at the time it was made.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('championship_registrations', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable()->after('driver_ids');
        });

        DB::table('championship_registrations')->update(['approved_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('championship_registrations', function (Blueprint $table) {
            $table->dropColumn('approved_at');
        });
    }
};
