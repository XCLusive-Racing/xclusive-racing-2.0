<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::table('esports_drivers')->where('name', 'Gianluca Walczak')->first();
        if (! $driver) return;

        $socials = json_decode($driver->socials ?? '[]', true) ?: [];

        $alreadyLinked = collect($socials)->contains(
            fn ($s) => ($s['type'] ?? null) === 'instagram' && ($s['href'] ?? null) === 'https://www.instagram.com/gianni.1966/'
        );

        if (! $alreadyLinked) {
            $socials[] = ['type' => 'instagram', 'href' => 'https://www.instagram.com/gianni.1966/'];
        }

        DB::table('esports_drivers')->where('id', $driver->id)->update([
            'socials'    => json_encode($socials),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $driver = DB::table('esports_drivers')->where('name', 'Gianluca Walczak')->first();
        if (! $driver) return;

        $socials = json_decode($driver->socials ?? '[]', true) ?: [];
        $socials = array_values(array_filter(
            $socials,
            fn ($s) => !(($s['type'] ?? null) === 'instagram' && ($s['href'] ?? null) === 'https://www.instagram.com/gianni.1966/')
        ));

        DB::table('esports_drivers')->where('id', $driver->id)->update([
            'socials'    => json_encode($socials),
            'updated_at' => now(),
        ]);
    }
};
