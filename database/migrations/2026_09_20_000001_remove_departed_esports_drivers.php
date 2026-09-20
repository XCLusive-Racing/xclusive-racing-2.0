<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Sergio Hernández and Mario García left the team. Team-event participation rows
// cascade away with the driver; result positions just lose their driver link
// (nullOnDelete).
return new class extends Migration
{
    public function up(): void
    {
        DB::table('esports_drivers')->whereIn('slug', ['sergio-hernandez', 'mario-garcia'])->delete();
    }

    public function down(): void
    {
        // Intentionally not restored — the drivers left the team.
    }
};
