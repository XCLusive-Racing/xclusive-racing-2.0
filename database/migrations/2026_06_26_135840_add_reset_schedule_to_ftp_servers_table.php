<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ftp_servers', function (Blueprint $table) {
            // 'rolling' = fixed reset cadence (SERVER 1/2/3), 'scheduled' = manual restart (SERVER 4)
            $table->string('server_type', 20)->default('rolling')->after('active');
            $table->unsignedTinyInteger('reset_start_hour')->default(0)->after('server_type');
            $table->unsignedSmallInteger('reset_interval_minutes')->default(120)->after('reset_start_hour');
        });
    }

    public function down(): void
    {
        Schema::table('ftp_servers', function (Blueprint $table) {
            $table->dropColumn(['server_type', 'reset_start_hour', 'reset_interval_minutes']);
        });
    }
};
