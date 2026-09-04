<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('race_registrations', function (Blueprint $table) {
            $table->softDeletes();

            // Change race_class_id FK from cascadeOnDelete to nullOnDelete
            // so deleting a race class never hard-deletes registrations at DB level
            $table->dropForeign(['race_class_id']);
            $table->foreign('race_class_id')->references('id')->on('race_classes')->nullOnDelete();
        });

        Schema::table('race_team_entries', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('race_registrations', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropForeign(['race_class_id']);
            $table->foreign('race_class_id')->references('id')->on('race_classes')->cascadeOnDelete();
        });

        Schema::table('race_team_entries', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};