<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('flag', 500)->nullable()->after('team');
            $table->decimal('sr_acc', 4, 2)->default(5.0)->after('elo_iracing');
            $table->decimal('sr_lmu', 4, 2)->default(5.0)->after('sr_acc');
            $table->decimal('sr_iracing', 4, 2)->default(5.0)->after('sr_lmu');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['flag', 'sr_acc', 'sr_lmu', 'sr_iracing']);
        });
    }
};