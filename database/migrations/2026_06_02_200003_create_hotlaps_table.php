<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotlaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->string('xuid_psid');
            $table->string('driver_name');
            $table->unsignedInteger('car_id')->nullable();
            $table->string('car_name')->nullable();
            $table->string('car_model')->nullable();
            $table->string('best_lap');
            $table->unsignedSmallInteger('laps_driven')->default(0);
            $table->decimal('xcl_rating_at_time', 10, 4)->default(0);
            $table->decimal('rating_change', 10, 4)->nullable();
            $table->decimal('new_xcl_rating', 10, 4)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotlaps');
    }
};