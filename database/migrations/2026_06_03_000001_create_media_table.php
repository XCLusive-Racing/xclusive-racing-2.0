<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('original_name');
            $table->string('path');
            $table->enum('type', ['image', 'video'])->default('image');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size')->default(0);
            $table->string('title', 255)->nullable();
            $table->string('alt_text', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};