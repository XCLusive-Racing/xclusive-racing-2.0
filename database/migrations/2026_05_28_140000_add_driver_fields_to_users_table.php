<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedSmallInteger('car_number')->nullable()->after('platform_id');
            $table->string('car_model', 100)->nullable()->after('car_number');
            $table->string('banner', 500)->nullable()->after('car_model');
            $table->string('game', 20)->nullable()->after('banner'); // acc, lmu, iracing
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['car_number', 'car_model', 'banner', 'game']);
        });
    }
};