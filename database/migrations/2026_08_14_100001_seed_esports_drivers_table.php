<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $roster = [
            'lmu' => [
                ['name' => 'Thomas Cauberghe',  'flag' => 'belgium',        'photo' => '/images/drivers/Cauberghe.png', 'socials' => [
                    ['type' => 'tiktok', 'href' => 'https://www.tiktok.com/@thomascauberghe?_r=1&_t=ZG-98h5kheJfzl'],
                    ['type' => 'twitch', 'href' => 'http://twitch.tv/thomascauberghee'],
                ]],
                ['name' => 'Giuseppe Dinoia',   'flag' => 'italy',          'photo' => '/images/drivers/Dinoia.png', 'socials' => [
                    ['type' => 'twitch',    'href' => 'https://www.twitch.tv/giuseppedinoia_21'],
                    ['type' => 'instagram', 'href' => 'https://www.instagram.com/xcl_giuseppedinoia?igsh=MWRxcTBldTNqd2Y3YQ=='],
                    ['type' => 'tiktok',    'href' => 'https://www.tiktok.com/@giuseppe_dinoia?_r=1&_t=ZN-95QVprGxrMJ'],
                ]],
                ['name' => 'Denis Ebert',        'flag' => 'germany',        'photo' => null, 'socials' => [
                    ['type' => 'instagram', 'href' => 'https://www.instagram.com/ebert_racing?igsh=MWY3N2ZwNnhmNWE4YQ=='],
                ]],
                ['name' => 'Lucas Britton',      'flag' => 'united%20kingdom', 'photo' => '/images/drivers/Britton.png', 'socials' => [
                    ['type' => 'instagram', 'href' => 'https://www.instagram.com/lucas_baaaaada/'],
                ]],
                ['name' => 'Wilson Gigé',        'flag' => 'france',         'photo' => '/images/drivers/W.Gige.png', 'socials' => [
                    ['type' => 'twitch',    'href' => 'https://www.twitch.tv/rxspectpapy'],
                    ['type' => 'instagram', 'href' => 'https://www.instagram.com/rxspect_papy?igsh=Y21hMGYzOWRtemEy'],
                    ['type' => 'tiktok',    'href' => 'https://www.tiktok.com/@rxspect.papy?_r=1&_t=ZN-95QWmWjw0s2'],
                ]],
                ['name' => 'Luca Gönnheimer',    'flag' => 'germany',        'photo' => '/images/drivers/goenni.png', 'socials' => [
                    ['type' => 'youtube',   'href' => 'https://www.youtube.com/'],
                    ['type' => 'instagram', 'href' => 'https://www.instagram.com/goenni98?igsh=Mzk3OW5oamxpbnR2'],
                ]],
                ['name' => 'Kyan Heyninck',      'flag' => 'belgium',        'photo' => '/images/drivers/heyninck.png', 'socials' => [
                    ['type' => 'youtube',   'href' => 'https://www.youtube.com/@kyanheyninck'],
                    ['type' => 'instagram', 'href' => 'https://www.instagram.com/kyan.heyninck/?hl=nl'],
                ]],
                ['name' => 'Alex Lucky',         'flag' => 'italy',          'photo' => '/images/drivers/A.Lucky.png', 'socials' => [
                    ['type' => 'instagram', 'href' => 'https://www.instagram.com/alexxluckyy?igsh=NWRleW9jbnRhaGlj'],
                    ['type' => 'tiktok',    'href' => 'https://www.tiktok.com/@alexxluckyy?_r=1&_t=ZN-95QVr8UQG06'],
                ]],
                ['name' => 'Paul Möller',        'flag' => 'germany',        'photo' => null, 'socials' => [
                    ['type' => 'instagram', 'href' => 'https://www.instagram.com/p.moeller787?igsh=bWh4Z3VpZjV0bDBk'],
                ]],
                ['name' => 'Thato Motubatse',    'flag' => 'south%20africa', 'photo' => '/images/drivers/motubatse.png', 'socials' => []],
                ['name' => 'Lukas Oesterreich',  'flag' => 'germany',        'photo' => '/images/drivers/Louk.png', 'socials' => [
                    ['type' => 'youtube',   'href' => 'https://www.youtube.com/@Louky99'],
                    ['type' => 'instagram', 'href' => 'https://www.instagram.com/speedlukas?igsh=OHRsbzFzMzA1OHl3'],
                ]],
                ['name' => 'Gianluca Walczak',   'flag' => 'germany',        'photo' => '/images/drivers/Walczak.png', 'socials' => []],
                ['name' => 'Kyle Williams',      'flag' => 'south%20africa', 'photo' => '/images/drivers/Williams.png', 'socials' => [
                    ['type' => 'instagram', 'href' => 'https://www.instagram.com/kyle.williams55?igsh=MXRhOWl1cmF5NjIwMA=='],
                ]],
                ['name' => 'Aidan Winchester',   'flag' => 'united%20kingdom','photo' => '/images/drivers/Winchester.png', 'socials' => [
                    ['type' => 'twitch',    'href' => 'https://www.twitch.tv/aidannn66'],
                    ['type' => 'instagram', 'href' => 'https://www.instagram.com/aidanwinchester66?igsh=bnZ5OWU4cHdwdWdn'],
                ]],
            ],
            'acc' => [
                ['name' => 'Nat Benett',         'flag' => 'united%20kingdom','photo' => '/images/drivers/Bennett.png', 'socials' => []],
                ['name' => 'Joakim Eriksson',    'flag' => null,             'photo' => '/images/drivers/Eriksson.png', 'socials' => []],
                ['name' => 'Fabio Faar',         'flag' => 'italy',          'photo' => null, 'socials' => []],
                ['name' => 'James Farish',       'flag' => 'united%20kingdom','photo' => '/images/drivers/J.Farish.png', 'socials' => []],
                ['name' => 'Will Friedmann',     'flag' => 'france',         'photo' => '/images/drivers/friedmann.png', 'socials' => []],
                ['name' => 'José García',        'flag' => null,             'photo' => '/images/drivers/Garcia.png', 'socials' => []],
                ['name' => 'Sergio Hernández',   'flag' => null,             'photo' => '/images/drivers/Hernández.png', 'socials' => []],
                ['name' => 'Matteo Mastromauro', 'flag' => 'italy',          'photo' => null, 'socials' => []],
                ['name' => 'Danny Meeldijk',     'flag' => 'netherlands',    'photo' => '/images/drivers/Danny.png', 'socials' => []],
                ['name' => 'Elmārs Miķelsons',   'flag' => 'latvia',         'photo' => '/images/drivers/elmars.png', 'socials' => []],
                ['name' => 'Florian Ochsmann',   'flag' => 'germany',        'photo' => '/images/drivers/ochsmann.png', 'socials' => []],
                ['name' => 'Phil Soucy',         'flag' => 'canada',         'photo' => null, 'socials' => []],
                ['name' => 'Gianluca Zambione',  'flag' => 'italy',          'photo' => '/images/drivers/Gianluca.png', 'socials' => []],
                ['name' => 'Federico Zamblera',  'flag' => 'italy',          'photo' => '/images/drivers/Zamby.png', 'socials' => []],
            ],
            'iracing' => [
                ['name' => 'Marcus Libz',       'flag' => 'canada',  'photo' => '/images/drivers/Libz.png', 'socials' => []],
                ['name' => 'James Curtin',      'flag' => 'usa',     'photo' => '/images/drivers/Curtin.png', 'socials' => []],
                ['name' => 'CJ Farish',         'flag' => 'usa',     'photo' => '/images/drivers/Mrshlk.png', 'socials' => []],
                ['name' => 'Mario García',      'flag' => null,      'photo' => '/images/drivers/Mare.png', 'socials' => []],
                ['name' => 'Jake Goldman',      'flag' => 'usa',     'photo' => '/images/drivers/Goldman2.png', 'socials' => []],
                ['name' => 'Michael Martinz',   'flag' => 'austria', 'photo' => null, 'socials' => []],
                ['name' => 'Menno Peters',      'flag' => 'netherlands', 'photo' => '/images/drivers/PetersM.png', 'socials' => []],
                ['name' => 'Parker Soukup',     'flag' => 'usa',     'photo' => '/images/drivers/P.Soukup.png', 'socials' => []],
                ['name' => 'André Damrat',      'flag' => 'germany', 'photo' => null, 'socials' => []],
            ],
        ];

        $now = now();
        $rows = [];
        $usedSlugs = [];

        foreach ($roster as $game => $drivers) {
            foreach ($drivers as $i => $d) {
                $baseSlug = Str::slug($d['name']);
                $slug     = $baseSlug;
                $suffix   = 2;
                while (in_array($slug, $usedSlugs, true)) {
                    $slug = $baseSlug . '-' . $suffix++;
                }
                $usedSlugs[] = $slug;

                $rows[] = [
                    'name'       => $d['name'],
                    'slug'       => $slug,
                    'game'       => $game,
                    'flag'       => $d['flag'],
                    'photo'      => $d['photo'],
                    'socials'    => json_encode($d['socials']),
                    'sort_order' => $i,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('esports_drivers')->insert($rows);
    }

    public function down(): void
    {
        DB::table('esports_drivers')->truncate();
    }
};
