<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('esports_drivers')->where('name', 'Brody Laweless')->exists()) {
            return;
        }

        $baseSlug = Str::slug('Brody Laweless');
        $slug     = $baseSlug;
        $suffix   = 2;
        while (DB::table('esports_drivers')->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $suffix++;
        }

        $nextSortOrder = (int) DB::table('esports_drivers')->where('game', 'lmu')->max('sort_order') + 1;

        $now = now();

        DB::table('esports_drivers')->insert([
            'name'       => 'Brody Laweless',
            'slug'       => $slug,
            'game'       => 'lmu',
            'flag'       => 'united%20kingdom',
            'photo'      => null,
            'socials'    => json_encode([]),
            'sort_order' => $nextSortOrder,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('esports_drivers')->where('name', 'Brody Laweless')->delete();
    }
};
