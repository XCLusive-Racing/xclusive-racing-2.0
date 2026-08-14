<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('esports_drivers')->where('slug', 'phil-soucy')->update([
            'photo'      => '/images/drivers/Phil Soucy edit.png',
            'updated_at' => $now,
        ]);

        DB::table('esports_drivers')->whereIn('slug', ['jose-garcia', 'sergio-hernandez'])->update([
            'flag'       => 'spain',
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('esports_drivers')->where('slug', 'phil-soucy')->update(['photo' => null]);
        DB::table('esports_drivers')->whereIn('slug', ['jose-garcia', 'sergio-hernandez'])->update(['flag' => null]);
    }
};
