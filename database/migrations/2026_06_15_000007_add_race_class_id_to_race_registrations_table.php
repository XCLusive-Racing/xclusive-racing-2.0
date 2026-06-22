<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('race_registrations', function (Blueprint $table) {
            $table->foreignId('race_class_id')->nullable()->after('user_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('race_registrations', function (Blueprint $table) {
            $table->dropForeign(['race_class_id']);
            $table->dropColumn('race_class_id');
        });
    }
};
