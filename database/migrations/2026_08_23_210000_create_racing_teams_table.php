<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('racing_teams', function (Blueprint $table) {
            $table->id();
            $table->string('name', 40);
            $table->string('tag', 6);
            // Also added by add_logo_to_racing_teams_table.php, whose timestamp
            // predates this table's creation — included here directly so a fresh
            // install ends up complete regardless of which migration ran first.
            $table->string('logo')->nullable();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('racing_teams');
    }
};