<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('event_formats', function (Blueprint $table) {
            $table->id();
            $table->string('game', 20);
            $table->string('name', 100);
            $table->string('formation_type', 60)->nullable();
            $table->unsignedSmallInteger('practice_mins')->default(0);
            $table->unsignedSmallInteger('quali_mins')->default(0);
            $table->unsignedSmallInteger('race1_mins')->default(0);
            $table->unsignedSmallInteger('quali2_mins')->nullable();
            $table->unsignedSmallInteger('race2_mins')->nullable();
            $table->string('pitstop_type', 30)->default('none');
            $table->unsignedTinyInteger('pitstop_count')->default(0);
            $table->unsignedSmallInteger('min_stop_secs')->nullable();
            $table->decimal('xcl_r_multiplier', 4, 2)->default(1.00);
            $table->string('server_preference', 20)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_formats');
    }
};
