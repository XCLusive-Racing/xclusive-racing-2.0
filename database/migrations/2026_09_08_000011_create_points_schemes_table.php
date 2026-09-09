<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('points_schemes', function (Blueprint $table) {
            $table->id();
            // Null means an XCL-provided template, available to every league.
            $table->foreignId('league_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('points_map'); // finishing position (1-based) => points
            $table->unsignedInteger('fastest_lap_points')->default(0);
            $table->unsignedInteger('pole_points')->default(0);
            $table->boolean('is_template')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('points_schemes');
    }
};
