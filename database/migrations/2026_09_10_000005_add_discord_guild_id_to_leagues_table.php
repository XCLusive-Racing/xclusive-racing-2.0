<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Phase 4 (docs/championships/PLAN.md): was in this feature's original Phase 1
// sketch but got dropped from what actually shipped — needed now to generalize
// DiscordRoleService's membership check beyond XCL's own single guild.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            $table->string('discord_guild_id')->nullable()->after('discord_invite_url');
        });
    }

    public function down(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            $table->dropColumn('discord_guild_id');
        });
    }
};
