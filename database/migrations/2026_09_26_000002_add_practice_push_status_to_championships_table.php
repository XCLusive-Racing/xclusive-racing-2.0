<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// The 24h practice server (settings.sessions.practice_server_enabled) is pushed every
// midnight by championships:push-practice — these record the last attempt so the
// wizard can show whether it worked, and for which round's track.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('championships', function (Blueprint $table) {
            $table->timestamp('practice_pushed_at')->nullable();
            $table->foreignId('practice_race_id')->nullable()->constrained('races')->nullOnDelete();
            $table->string('practice_push_error', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('championships', function (Blueprint $table) {
            $table->dropConstrainedForeignId('practice_race_id');
            $table->dropColumn(['practice_pushed_at', 'practice_push_error']);
        });
    }
};
