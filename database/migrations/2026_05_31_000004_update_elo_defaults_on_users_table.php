<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('elo_acc')->default(1500)->change();
            $table->integer('elo_lmu')->default(1500)->change();
            $table->integer('elo_iracing')->default(1500)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('elo_acc')->default(1200)->change();
            $table->integer('elo_lmu')->default(1200)->change();
            $table->integer('elo_iracing')->default(1200)->change();
        });
    }
};