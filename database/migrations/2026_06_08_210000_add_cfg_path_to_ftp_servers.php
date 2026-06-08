<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ftp_servers', function (Blueprint $table) {
            $table->string('cfg_path')->default('/cfg')->after('path');
        });
    }

    public function down(): void
    {
        Schema::table('ftp_servers', function (Blueprint $table) {
            $table->dropColumn('cfg_path');
        });
    }
};