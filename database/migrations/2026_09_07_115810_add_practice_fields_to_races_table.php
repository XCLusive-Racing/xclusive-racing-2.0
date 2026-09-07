<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->boolean('has_practice_server')->default(false)->after('config_overrides');
            $table->text('practice_notes')->nullable()->after('has_practice_server');
        });
    }

    public function down(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->dropColumn(['has_practice_server', 'practice_notes']);
        });
    }
};
