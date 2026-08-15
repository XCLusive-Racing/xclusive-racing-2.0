<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // Fabio Faar: swap old YouTube handle for the new one
        $faar = DB::table('esports_drivers')->where('slug', 'fabio-faar')->first();
        if ($faar) {
            $socials = json_decode($faar->socials ?? '[]', true) ?: [];
            $socials = array_values(array_filter(
                $socials,
                fn ($s) => ($s['type'] ?? null) !== 'youtube'
            ));
            $socials[] = ['type' => 'youtube', 'href' => 'https://www.youtube.com/@faar9626'];

            DB::table('esports_drivers')->where('id', $faar->id)->update([
                'socials'    => json_encode($socials),
                'updated_at' => $now,
            ]);
        }

        DB::table('esports_drivers')->where('slug', 'albert-synnerstrom')->update([
            'socials' => json_encode([
                ['type' => 'instagram', 'href' => 'https://www.instagram.com/albertsynnerstrom/'],
            ]),
            'updated_at' => $now,
        ]);

        DB::table('esports_drivers')->where('slug', 'will-friedmann')->update([
            'socials' => json_encode([
                ['type' => 'instagram', 'href' => 'https://www.instagram.com/willfriedmann'],
                ['type' => 'youtube',   'href' => 'https://www.youtube.com/channel/UCajN95mk27sxdc5CAznNE_A'],
            ]),
            'updated_at' => $now,
        ]);

        DB::table('esports_drivers')->where('slug', 'florian-ochsmann')->update([
            'socials' => json_encode([
                ['type' => 'instagram', 'href' => 'https://www.instagram.com/ochsmannflorian'],
            ]),
            'updated_at' => $now,
        ]);

        DB::table('esports_drivers')->where('slug', 'gianluca-zambione')->update([
            'socials' => json_encode([
                ['type' => 'tiktok', 'href' => 'https://www.tiktok.com/@nexfanatic?_r=1&_t=ZN-98sW9L7nRCw'],
                ['type' => 'twitch', 'href' => 'https://m.twitch.tv/nexfanatic/home'],
            ]),
            'updated_at' => $now,
        ]);

        DB::table('esports_drivers')->where('slug', 'elmars-mikelsons')->update([
            'socials' => json_encode([
                ['type' => 'instagram', 'href' => 'https://www.instagram.com/e_mikelsons_71?igsi=eWo1NTF0M292dnJt&utm_source=qr'],
                ['type' => 'youtube',   'href' => 'https://youtube.com/@problematv2811?si=smRoTgnL6_X6hBk_'],
            ]),
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        $now = now();

        DB::table('esports_drivers')->where('slug', 'fabio-faar')->update([
            'socials' => json_encode([
                ['type' => 'instagram', 'href' => 'https://www.instagram.com/Faaar_96/'],
                ['type' => 'twitch',    'href' => 'https://www.twitch.tv/faar_96'],
                ['type' => 'youtube',   'href' => 'https://www.youtube.com/@FAAR_96'],
            ]),
            'updated_at' => $now,
        ]);

        DB::table('esports_drivers')->where('slug', 'albert-synnerstrom')->update(['socials' => json_encode([])]);
        DB::table('esports_drivers')->where('slug', 'will-friedmann')->update(['socials' => json_encode([])]);
        DB::table('esports_drivers')->where('slug', 'florian-ochsmann')->update(['socials' => json_encode([])]);
        DB::table('esports_drivers')->where('slug', 'gianluca-zambione')->update(['socials' => json_encode([])]);
        DB::table('esports_drivers')->where('slug', 'elmars-mikelsons')->update(['socials' => json_encode([])]);
    }
};
