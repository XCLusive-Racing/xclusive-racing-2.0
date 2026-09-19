<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('result_race_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_race_id')->constrained()->cascadeOnDelete();
            // Null for pro subjects (the result's own subject already identifies the driver).
            $table->foreignId('esports_driver_id')->nullable()->constrained()->nullOnDelete();
            $table->string('position', 10); // "P4", "DNF", "DNS", etc.
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_race_positions');
    }
};
