<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_article_tag', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained('news_articles')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('news_tags')->cascadeOnDelete();
            $table->primary(['article_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_article_tag');
    }
};
