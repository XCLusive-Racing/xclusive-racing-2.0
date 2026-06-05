<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE media MODIFY COLUMN type ENUM('image','icon','video','youtube') NOT NULL DEFAULT 'image'");

        Schema::table('media', function (Blueprint $table) {
            $table->string('youtube_id', 20)->nullable()->after('type');
            $table->string('category', 100)->nullable()->after('alt_text');
            $table->string('filename')->nullable()->change();
            $table->string('original_name')->nullable()->change();
            $table->string('path')->nullable()->change();
            $table->string('mime_type', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn(['youtube_id', 'category']);
        });

        DB::statement("ALTER TABLE media MODIFY COLUMN type ENUM('image','video') NOT NULL DEFAULT 'image'");

        Schema::table('media', function (Blueprint $table) {
            $table->string('filename')->nullable(false)->change();
            $table->string('original_name')->nullable(false)->change();
            $table->string('path')->nullable(false)->change();
            $table->string('mime_type', 100)->nullable(false)->change();
        });
    }
};