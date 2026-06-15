<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->foreignId('championship_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->integer('round_number')->nullable()->after('championship_id');
            $table->boolean('is_multiclass')->default(false)->after('round_number');
        });
    }

    public function down(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->dropForeign(['championship_id']);
            $table->dropColumn(['championship_id', 'round_number', 'is_multiclass']);
        });
    }
};