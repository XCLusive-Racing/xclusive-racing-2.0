<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_event_drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('esports_driver_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['team_event_id', 'esports_driver_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_event_drivers');
    }
};
