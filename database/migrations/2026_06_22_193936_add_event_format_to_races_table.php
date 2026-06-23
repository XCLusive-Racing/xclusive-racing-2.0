<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->foreignId('event_format_id')->nullable()->constrained('event_formats')->nullOnDelete()->after('id');
            $table->string('max_rating', 20)->nullable()->after('min_rating');
        });
    }

    public function down(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->dropForeign(['event_format_id']);
            $table->dropColumn(['event_format_id', 'max_rating']);
        });
    }
};
