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
            $table->json('settings_defaults')->nullable()->after('cfg_path');
            $table->json('eventrules_defaults')->nullable()->after('settings_defaults');
            $table->json('assistrules_defaults')->nullable()->after('eventrules_defaults');
        });
    }

    public function down(): void
    {
        Schema::table('ftp_servers', function (Blueprint $table) {
            $table->dropColumn(['settings_defaults', 'eventrules_defaults', 'assistrules_defaults']);
        });
    }
};
