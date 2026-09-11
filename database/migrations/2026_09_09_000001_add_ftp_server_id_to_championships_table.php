<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// The championship-wide default server, set once on the Basics step. A round
// still carries its own ftp_server_id (races table) so it can be overridden
// per round — this column only supplies the default the Add Round form
// pre-selects.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('championships', function (Blueprint $table) {
            $table->foreignId('ftp_server_id')->nullable()->after('league_id')
                  ->constrained('ftp_servers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('championships', function (Blueprint $table) {
            $table->dropForeign(['ftp_server_id']);
            $table->dropColumn('ftp_server_id');
        });
    }
};
