<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('esports_drivers')->where('slug', 'michael-martinz')->update([
            'photo'      => '/images/drivers/Martinz.png',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('esports_drivers')->where('slug', 'michael-martinz')->update(['photo' => null]);
    }
};
