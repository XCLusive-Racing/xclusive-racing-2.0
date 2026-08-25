<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('esports_drivers')->where('name', 'Melvin Milasten')->exists()) {
            return;
        }

        $baseSlug = Str::slug('Melvin Milasten');
        $slug     = $baseSlug;
        $suffix   = 2;
        while (DB::table('esports_drivers')->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $suffix++;
        }

        $nextSortOrder = (int) DB::table('esports_drivers')->where('game', 'acc')->max('sort_order') + 1;

        $now = now();

        DB::table('esports_drivers')->insert([
            'name'       => 'Melvin Milasten',
            'slug'       => $slug,
            'game'       => 'acc',
            'flag'       => 'sweden',
            'photo'      => '/images/drivers/m.milasten.png',
            'socials'    => json_encode([
                ['type' => 'tiktok', 'href' => 'https://www.tiktok.com/@mellemelon6823'],
                ['type' => 'instagram', 'href' => 'https://www.instagram.com/melvinmilasten'],
                ['type' => 'twitch', 'href' => 'https://www.twitch.tv/melle234353'],
            ]),
            'sort_order' => $nextSortOrder,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('esports_drivers')->where('name', 'Melvin Milasten')->delete();
    }
};
