<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_applications', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('discord')->nullable();
            $table->string('role');
            $table->json('platforms')->nullable();
            $table->text('motivation');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_applications');
    }
};
