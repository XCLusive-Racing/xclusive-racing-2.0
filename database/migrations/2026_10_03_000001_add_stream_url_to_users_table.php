<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A supporter's own Twitch/YouTube channel, set on their profile; they add it to an
// event's streamers bar with that event's + Add my stream button.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('stream_url', 255)->nullable()->after('team');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('stream_url');
        });
    }
};
