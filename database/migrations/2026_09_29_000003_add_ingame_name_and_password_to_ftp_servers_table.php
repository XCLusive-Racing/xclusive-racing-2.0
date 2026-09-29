<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// What drivers see in ACC's server list and type to join. Until now every server
// with a number was pushed as "XCL SERVER n" / "nxcl" — a league's own server
// too. Null keeps today's name/password (AccServerConfigService::settings()).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ftp_servers', function (Blueprint $table) {
            $table->string('ingame_name', 100)->nullable()->after('name');
            $table->string('ingame_password', 50)->nullable()->after('ingame_name');
        });
    }

    public function down(): void
    {
        Schema::table('ftp_servers', function (Blueprint $table) {
            $table->dropColumn(['ingame_name', 'ingame_password']);
        });
    }
};
