<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // Add Albert Synnerström to the ACC roster
        if (! DB::table('esports_drivers')->where('name', 'Albert Synnerström')->exists()) {
            $baseSlug = Str::slug('Albert Synnerström');
            $slug     = $baseSlug;
            $suffix   = 2;
            while (DB::table('esports_drivers')->where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $suffix++;
            }

            $nextSortOrder = (int) DB::table('esports_drivers')->where('game', 'acc')->max('sort_order') + 1;

            DB::table('esports_drivers')->insert([
                'name'       => 'Albert Synnerström',
                'slug'       => $slug,
                'game'       => 'acc',
                'flag'       => 'sweden',
                'photo'      => '/images/drivers/a.synnerstrom.png',
                'socials'    => json_encode([]),
                'sort_order' => $nextSortOrder,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Joakim Eriksson: Swedish flag + Instagram
        $eriksson = DB::table('esports_drivers')->where('name', 'Joakim Eriksson')->first();
        if ($eriksson) {
            $socials = json_decode($eriksson->socials ?? '[]', true) ?: [];
            $alreadyLinked = collect($socials)->contains(
                fn ($s) => ($s['type'] ?? null) === 'instagram' && ($s['href'] ?? null) === 'https://www.instagram.com/franticjuttjutt?igsh=N2JnendxaHhzYnc1'
            );
            if (! $alreadyLinked) {
                $socials[] = ['type' => 'instagram', 'href' => 'https://www.instagram.com/franticjuttjutt?igsh=N2JnendxaHhzYnc1'];
            }

            DB::table('esports_drivers')->where('id', $eriksson->id)->update([
                'flag'       => 'sweden',
                'socials'    => json_encode($socials),
                'updated_at' => $now,
            ]);
        }

        // Fabio Faar: uploaded photo
        DB::table('esports_drivers')->where('name', 'Fabio Faar')->update([
            'photo'      => '/images/drivers/f.faar.png',
            'updated_at' => $now,
        ]);

        // James Farish -> Fazzy Binx (slug kept as-is so any existing links keep working)
        DB::table('esports_drivers')->where('name', 'James Farish')->update([
            'name'       => 'Fazzy Binx',
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('esports_drivers')->where('name', 'Albert Synnerström')->delete();

        DB::table('esports_drivers')->where('name', 'Fazzy Binx')->update([
            'name' => 'James Farish',
        ]);
    }
};
