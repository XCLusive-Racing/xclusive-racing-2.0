<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('race_results', function (Blueprint $table) {
            $table->decimal('rating_before', 10, 4)->nullable()->after('dnf');
            $table->decimal('rating_after',  10, 4)->nullable()->after('rating_before');
            $table->decimal('elo_change',    10, 4)->nullable()->after('rating_after');
            $table->decimal('sof',           10, 2)->nullable()->after('elo_change');
        });
    }

    public function down(): void
    {
        Schema::table('race_results', function (Blueprint $table) {
            $table->dropColumn(['rating_before', 'rating_after', 'elo_change', 'sof']);
        });
    }
};