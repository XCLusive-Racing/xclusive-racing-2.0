<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Time Trial uploads no longer target a specific server restart (the server can't be
// restarted remotely, so the config is simply uploaded every hour), so the restart each
// push was for isn't tracked any more.
//
// forced_entry_list: per event, upload a forced entry list of only the signups instead of
// the open list of every member. Only useful if the server reloads the entry list without
// a restart, which is still to be tested.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('time_trial_events', function (Blueprint $table) {
            $table->dropColumn('last_pushed_for');
            $table->boolean('forced_entry_list')->default(false)->after('is_published');
        });
    }

    public function down(): void
    {
        Schema::table('time_trial_events', function (Blueprint $table) {
            $table->dropColumn('forced_entry_list');
            $table->timestamp('last_pushed_for')->nullable()->after('is_published');
        });
    }
};
