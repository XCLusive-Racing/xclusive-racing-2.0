<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('race_registrations', function (Blueprint $table) {
            $table->foreignId('team_entry_id')
                ->nullable()
                ->after('race_class_id')
                ->constrained('race_team_entries')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('race_registrations', function (Blueprint $table) {
            $table->dropForeign(['team_entry_id']);
            $table->dropColumn('team_entry_id');
        });
    }
};