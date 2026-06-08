<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bops', function (Blueprint $table) {
            $table->id();
            $table->string('game', 20);
            $table->string('car_model');
            $table->string('track')->nullable();
            $table->integer('ballast_kg')->default(0);
            $table->integer('restrictor')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bops');
    }
};