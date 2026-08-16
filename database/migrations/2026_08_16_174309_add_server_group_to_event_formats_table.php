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
        Schema::table('event_formats', function (Blueprint $table) {
            $table->string('server_group', 20)->nullable()->after('server_preference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_formats', function (Blueprint $table) {
            $table->dropColumn('server_group');
        });
    }
};
