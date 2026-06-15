<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('championships', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('game');
            $table->integer('season');
            $table->string('status')->default('draft');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('icon')->nullable();
            $table->integer('max_drivers')->nullable();
            $table->boolean('is_multiclass')->default(false);
            $table->json('points_system')->nullable();
            $table->integer('bonus_fastest_lap')->default(0);
            $table->integer('bonus_pole')->default(0);
            $table->integer('drop_rounds')->default(0);
            $table->integer('max_missed_rounds')->nullable();
            $table->integer('min_rounds_to_qualify')->nullable();
            $table->boolean('registration_open')->default(false);
            $table->timestamp('registration_deadline')->nullable();
            $table->string('sr_requirement')->nullable();
            $table->string('min_rating')->nullable();
            $table->string('car_class')->nullable();
            $table->integer('practice_duration')->nullable();
            $table->integer('qualifying_duration')->nullable();
            $table->integer('race_duration')->nullable();
            $table->string('weather')->nullable();
            $table->string('time_of_day')->nullable();
            $table->string('duration_key')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('championships');
    }
};