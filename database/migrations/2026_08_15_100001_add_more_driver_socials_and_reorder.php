<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('esports_drivers')->where('slug', 'jose-garcia')->update([
            'socials' => json_encode([
                ['type' => 'tiktok',    'href' => 'https://www.tiktok.com/@xdabyor?_r=1&_t=ZN-98t9Xw68Q8U'],
                ['type' => 'instagram', 'href' => 'https://www.instagram.com/morrilloo.grc?igsh=MTc0eHRnZW43cDZv'],
            ]),
            'updated_at' => $now,
        ]);

        DB::table('esports_drivers')->where('slug', 'federico-zamblera')->update([
            'socials' => json_encode([
                ['type' => 'instagram', 'href' => 'https://www.instagram.com/federico_zamblera/'],
            ]),
            'updated_at' => $now,
        ]);

        DB::table('esports_drivers')->where('slug', 'matteo-mastromauro')->update([
            'socials' => json_encode([
                ['type' => 'instagram', 'href' => 'https://www.instagram.com/_m4stro_/'],
            ]),
            'updated_at' => $now,
        ]);

        DB::table('esports_drivers')->where('slug', 'james-farish')->update([ // Fazzy Binx
            'socials' => json_encode([
                ['type' => 'twitch', 'href' => 'https://www.twitch.tv/fazzy_binx'],
            ]),
            'updated_at' => $now,
        ]);

        DB::table('esports_drivers')->where('slug', 'cj-farish')->update([
            'socials' => json_encode([
                ['type' => 'twitch', 'href' => 'https://www.twitch.tv/mrshl88moss/'],
            ]),
            'updated_at' => $now,
        ]);

        DB::table('esports_drivers')->where('slug', 'james-curtin')->update([
            'socials' => json_encode([
                ['type' => 'twitch',    'href' => 'https://www.twitch.tv/jcurtin413'],
                ['type' => 'instagram', 'href' => 'https://www.instagram.com/jamescurtin2/'],
            ]),
            'updated_at' => $now,
        ]);

        DB::table('esports_drivers')->where('slug', 'parker-soukup')->update([
            'socials' => json_encode([
                ['type' => 'twitch',    'href' => 'https://www.twitch.tv/radioactive9999'],
                ['type' => 'instagram', 'href' => 'https://www.instagram.com/parker_soukup/'],
            ]),
            'updated_at' => $now,
        ]);

        DB::table('esports_drivers')->where('slug', 'jake-goldman')->update([
            'socials' => json_encode([
                ['type' => 'instagram', 'href' => 'https://www.instagram.com/jgoldman1226/'],
            ]),
            'updated_at' => $now,
        ]);

        DB::table('esports_drivers')->where('slug', 'michael-martinz')->update([
            'socials' => json_encode([
                ['type' => 'instagram', 'href' => 'https://www.instagram.com/martinz.michael/'],
            ]),
            'updated_at' => $now,
        ]);

        DB::table('esports_drivers')->where('slug', 'menno-peters')->update([
            'socials' => json_encode([
                ['type' => 'instagram', 'href' => 'https://www.instagram.com/mennopeterss/'],
            ]),
            'updated_at' => $now,
        ]);

        DB::table('esports_drivers')->where('slug', 'andre-damrat')->update([
            'socials' => json_encode([
                ['type' => 'instagram', 'href' => 'https://www.instagram.com/andredamr/'],
            ]),
            'updated_at' => $now,
        ]);

        // Move Albert Synnerström back into alphabetical order: right after Phil Soucy,
        // before Gianluca Zambione / Federico Zamblera.
        DB::table('esports_drivers')->where('slug', 'albert-synnerstrom')->update(['sort_order' => 12, 'updated_at' => $now]);
        DB::table('esports_drivers')->where('slug', 'gianluca-zambione')->update(['sort_order' => 13, 'updated_at' => $now]);
        DB::table('esports_drivers')->where('slug', 'federico-zamblera')->update(['sort_order' => 14, 'updated_at' => $now]);
    }

    public function down(): void
    {
        $now = now();

        foreach (['jose-garcia', 'federico-zamblera', 'matteo-mastromauro', 'james-farish', 'cj-farish', 'james-curtin', 'parker-soukup', 'jake-goldman', 'michael-martinz', 'menno-peters', 'andre-damrat'] as $slug) {
            DB::table('esports_drivers')->where('slug', $slug)->update(['socials' => json_encode([]), 'updated_at' => $now]);
        }

        DB::table('esports_drivers')->where('slug', 'albert-synnerstrom')->update(['sort_order' => 14, 'updated_at' => $now]);
        DB::table('esports_drivers')->where('slug', 'gianluca-zambione')->update(['sort_order' => 12, 'updated_at' => $now]);
        DB::table('esports_drivers')->where('slug', 'federico-zamblera')->update(['sort_order' => 13, 'updated_at' => $now]);
    }
};
