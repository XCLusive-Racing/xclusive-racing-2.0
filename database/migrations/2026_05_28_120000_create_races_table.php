<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('races', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('game', ['acc', 'lmu', 'iracing']);
            $table->string('track');
            $table->timestamp('scheduled_at');
            $table->enum('status', ['open', 'closed', 'finished'])->default('open');
            $table->unsignedInteger('max_drivers')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('races');
    }
};