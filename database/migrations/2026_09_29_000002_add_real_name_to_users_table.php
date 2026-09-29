<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// A driver can add their real name and choose, on their profile, whether the site
// shows it or their gamertag (users.name). display_name_preference existed but was
// never read, so every stored value is reset to the gamertag everyone sees today.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name', 50)->nullable()->after('name');
            $table->string('last_name', 50)->nullable()->after('first_name');
            $table->string('display_name_preference')->default('gamertag')->change();
        });

        DB::table('users')->update(['display_name_preference' => 'gamertag']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name']);
            $table->string('display_name_preference')->default('name')->change();
        });
    }
};
