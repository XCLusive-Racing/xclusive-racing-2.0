<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('esports_drivers')->where('slug', 'thato-motubatse')->update([
            'socials' => json_encode([
                ['type' => 'instagram', 'href' => 'https://www.instagram.com/cozy_racing/'],
            ]),
            'updated_at' => $now,
        ]);

        DB::table('esports_drivers')->where('slug', 'marcus-libz')->update([
            'socials' => json_encode([
                ['type' => 'instagram', 'href' => 'https://www.instagram.com/marcus.libz?igsh=a3pzczRldGd4YTJ5&utm_source=qr'],
                ['type' => 'twitch',    'href' => 'https://m.twitch.tv/marcuslibz/home?tt_content=channel&tt_medium=mobile_web_share'],
                ['type' => 'youtube',   'href' => 'https://youtube.com/@marcuslibz?si=p56PXT_HkoaCBfkx'],
            ]),
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        $now = now();
        DB::table('esports_drivers')->where('slug', 'thato-motubatse')->update(['socials' => json_encode([]), 'updated_at' => $now]);
        DB::table('esports_drivers')->where('slug', 'marcus-libz')->update(['socials' => json_encode([]), 'updated_at' => $now]);
    }
};
