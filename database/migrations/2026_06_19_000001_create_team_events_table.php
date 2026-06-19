<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_events', function (Blueprint $table) {
            $table->id();
            $table->string('subject');          // e.g. dirk-schouten, acc-team
            $table->string('title');            // e.g. "Lausitzring: Race 1"
            $table->string('subtitle')->nullable(); // e.g. "Porsche Sixt Carrera Cup Deutschland"
            $table->dateTime('starts_at');
            $table->string('watch_url', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_events');
    }
};
