<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_article_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('news_articles')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['article_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_article_likes');
    }
};
