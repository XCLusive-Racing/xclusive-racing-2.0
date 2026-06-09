<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->string('sr_requirement')->nullable()->after('car_class');
            $table->string('min_rating')->nullable()->after('sr_requirement');
        });
    }

    public function down(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->dropColumn(['sr_requirement', 'min_rating']);
        });
    }
};