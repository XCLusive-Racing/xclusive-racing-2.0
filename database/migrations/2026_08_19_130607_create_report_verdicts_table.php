<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per steward per report. Backs the "more than 2 stewards can add a
     * verdict" flow — the reports table's steward_1 and steward_2 columns stay in
     * sync as a denormalized mirror of whichever two stewards hold those assignment
     * slots, but agreement/red-flag checks are computed from this table so any
     * number of stewards can participate.
     */
    public function up(): void
    {
        Schema::create('report_verdicts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('steward_id')->constrained('users')->cascadeOnDelete();
            $table->string('penalty', 10);
            $table->decimal('multiplier', 3, 1)->default(1.0);
            $table->boolean('red_flag')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['report_id', 'steward_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_verdicts');
    }
};
