<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Wilson Gigé left the LMU team. Team-event participation rows cascade away with
// the driver; result positions just lose their driver link (nullOnDelete).
return new class extends Migration
{
    public function up(): void
    {
        DB::table('esports_drivers')->where('slug', 'wilson-gige')->delete();
    }

    public function down(): void
    {
        // Intentionally not restored — the driver left the team.
    }
};
