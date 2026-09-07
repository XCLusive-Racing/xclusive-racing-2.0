<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('practice_server_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('race_id')->constrained('races')->cascadeOnDelete();
            $table->foreignId('practice_server_id')->constrained('practice_servers');

            // Computed once from the restart cadence and persisted, not recalculated on
            // every read — see PracticeWindowCalculator. window_start is the "go live"
            // restart boundary, upload_at is one minute before it (also the registration
            // cutoff shown to drivers), window_end is the event's own start time.
            $table->timestamp('window_start');
            $table->timestamp('upload_at');
            $table->timestamp('window_end');

            // scheduled -> pushing -> live -> completed, or failed / cancelled / too_late.
            $table->string('status')->default('scheduled');

            $table->timestamp('pushed_at')->nullable();
            $table->unsignedInteger('entry_count')->nullable();
            $table->text('last_error')->nullable();

            $table->timestamps();

            $table->index(['practice_server_id', 'window_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('practice_server_sessions');
    }
};
