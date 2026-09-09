<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Widened to hold encrypted ciphertext, the same way `password` already does —
// FTP credentials are encrypted at rest and never rendered back to the browser.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ftp_servers', function (Blueprint $table) {
            $table->text('username')->change();
        });
    }

    public function down(): void
    {
        Schema::table('ftp_servers', function (Blueprint $table) {
            $table->string('username')->change();
        });
    }
};
