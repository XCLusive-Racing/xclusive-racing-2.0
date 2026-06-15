<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('race_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('race_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color')->default('#db2777');
            $table->string('car_class')->nullable();
            $table->integer('max_drivers')->nullable();
            $table->string('sr_requirement')->nullable();
            $table->string('min_rating')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('race_classes');
    }
};
