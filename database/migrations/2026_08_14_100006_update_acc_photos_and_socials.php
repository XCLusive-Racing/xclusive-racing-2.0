<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('esports_drivers')->where('slug', 'phil-soucy')->update([
            'photo'      => '/images/drivers/p.soucy.png',
            'updated_at' => $now,
        ]);

        DB::table('esports_drivers')->where('name', 'Denis Ebert')->update([
            'photo'      => '/images/drivers/d.ebert.png',
            'updated_at' => $now,
        ]);

        DB::table('esports_drivers')->where('slug', 'fabio-faar')->update([
            'socials' => json_encode([
                ['type' => 'instagram', 'href' => 'https://www.instagram.com/Faaar_96/'],
                ['type' => 'twitch',    'href' => 'https://www.twitch.tv/faar_96'],
                ['type' => 'youtube',   'href' => 'https://www.youtube.com/@FAAR_96'],
            ]),
            'updated_at' => $now,
        ]);

        DB::table('esports_drivers')->where('slug', 'sergio-hernandez')->update([
            'socials' => json_encode([
                ['type' => 'instagram', 'href' => 'https://www.instagram.com/shertor08?igsh=c3hibTYwaGFzbThx'],
                ['type' => 'twitch',    'href' => 'https://www.twitch.tv/xcl_shertor08'],
                ['type' => 'tiktok',    'href' => 'https://www.tiktok.com/@sergioht08?_r=1&_t=ZN-98sMeOBBhji'],
            ]),
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('esports_drivers')->where('slug', 'phil-soucy')->update(['photo' => null]);
        DB::table('esports_drivers')->where('name', 'Denis Ebert')->update(['photo' => null]);
        DB::table('esports_drivers')->where('slug', 'fabio-faar')->update(['socials' => json_encode([])]);
        DB::table('esports_drivers')->where('slug', 'sergio-hernandez')->update(['socials' => json_encode([])]);
    }
};
