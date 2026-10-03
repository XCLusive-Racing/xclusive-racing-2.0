<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A supporter's Twitch/YouTube link for one race, shown in the event page's streamers bar.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('race_registrations', function (Blueprint $table) {
            $table->string('stream_url', 255)->nullable()->after('team_entry_id');
        });
    }

    public function down(): void
    {
        Schema::table('race_registrations', function (Blueprint $table) {
            $table->dropColumn('stream_url');
        });
    }
};
