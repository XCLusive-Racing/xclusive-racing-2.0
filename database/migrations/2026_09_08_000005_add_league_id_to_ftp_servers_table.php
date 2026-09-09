<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ftp_servers', function (Blueprint $table) {
            // Null means the server belongs to XCL itself, not any league.
            $table->foreignId('league_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ftp_servers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('league_id');
        });
    }
};
