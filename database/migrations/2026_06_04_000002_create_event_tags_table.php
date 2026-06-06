<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color', 7)->default('#7B2FBE');
            $table->timestamps();
        });

        DB::table('event_tags')->insert([
            ['name' => 'Daily',        'slug' => 'daily',        'color' => '#00B4A0', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Weekly',       'slug' => 'weekly',       'color' => '#2563eb', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Championship', 'slug' => 'championship', 'color' => '#7B2FBE', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('event_tags');
    }
};