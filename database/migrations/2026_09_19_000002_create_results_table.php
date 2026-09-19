<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('results', function (Blueprint $table) {
            $table->id();
            // TeamEvent::subjects() key: dirk-schouten, mats-van-rooijen, acc-team, lmu-team, iracing-team
            $table->string('subject');
            $table->enum('category', ['pro', 'esports']);
            $table->unsignedSmallInteger('year');
            $table->string('title')->nullable(); // championship name (pro) / event or series name (esports)
            $table->string('standing')->nullable(); // pro only: free-text end-of-season standing
            $table->text('notes')->nullable(); // esports only: optional notes
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['subject', 'year']);
            $table->index(['category', 'subject']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};
