<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('racing_team_members', function (Blueprint $table) {
            // 'member' or 'manager' -- a manager is trusted the same as the owner for
            // registering/entering the team into an event or championship, but can't
            // manage the roster, logo, or delete the team (owner-only, unchanged).
            $table->string('role')->default('member')->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('racing_team_members', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
