<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('esports_drivers')->where('name', 'Jure Artač Vičič')->exists()) {
            return;
        }

        $baseSlug = Str::slug('Jure Artac Vicic');
        $slug     = $baseSlug;
        $suffix   = 2;
        while (DB::table('esports_drivers')->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $suffix++;
        }

        $insertSortOrder = DB::table('esports_drivers')
            ->where('game', 'lmu')
            ->where('name', 'Gianluca Walczak')
            ->value('sort_order');

        if ($insertSortOrder === null) {
            $insertSortOrder = (int) DB::table('esports_drivers')->where('game', 'lmu')->max('sort_order') + 1;
        } else {
            DB::table('esports_drivers')
                ->where('game', 'lmu')
                ->where('sort_order', '>=', $insertSortOrder)
                ->increment('sort_order');
        }

        $now = now();

        DB::table('esports_drivers')->insert([
            'name'       => 'Jure Artač Vičič',
            'slug'       => $slug,
            'game'       => 'lmu',
            'flag'       => null,
            'photo'      => '/images/drivers/jure.png',
            'socials'    => json_encode([
                ['type' => 'twitch', 'href' => 'https://www.twitch.tv/jure_av'],
                ['type' => 'youtube', 'href' => 'https://www.youtube.com/@JureAV'],
                ['type' => 'youtube', 'href' => 'https://www.youtube.com/@JureAV2'],
                ['type' => 'instagram', 'href' => 'https://www.instagram.com/jure_av/'],
            ]),
            'sort_order' => $insertSortOrder,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        $driver = DB::table('esports_drivers')->where('name', 'Jure Artač Vičič')->first();

        if (! $driver) {
            return;
        }

        DB::table('esports_drivers')->where('id', $driver->id)->delete();

        DB::table('esports_drivers')
            ->where('game', 'lmu')
            ->where('sort_order', '>', $driver->sort_order)
            ->decrement('sort_order');
    }
};
