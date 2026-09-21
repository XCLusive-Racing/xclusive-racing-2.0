<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// José García moves from the ACC console team to the LMU team.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('esports_drivers')->where('slug', 'jose-garcia')->update(['game' => 'lmu']);
    }

    public function down(): void
    {
        DB::table('esports_drivers')->where('slug', 'jose-garcia')->update(['game' => 'acc']);
    }
};
