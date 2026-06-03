<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('gamertag')->unique();
            $table->unsignedSmallInteger('number')->nullable();
            $table->decimal('xcl_rating', 8, 2)->default(1500.00);
            $table->string('xuid_psid')->nullable()->unique();
            $table->decimal('safety_rating', 4, 2)->default(4.00);
            $table->unsignedSmallInteger('dns_count')->default(0);
            $table->string('discord')->nullable();
            $table->string('abbreviation', 3)->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->char('country_code', 2)->nullable();
            $table->string('car')->nullable();
            $table->unsignedInteger('car_id')->nullable();
            $table->string('team')->nullable();
            $table->date('date_joined')->nullable();
            $table->enum('platform', ['xbox', 'psn'])->default('psn');
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};