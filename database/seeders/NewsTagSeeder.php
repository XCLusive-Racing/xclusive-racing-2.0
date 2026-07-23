<?php

namespace Database\Seeders;

use App\Models\NewsTag;
use Illuminate\Database\Seeder;

class NewsTagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            ['name' => 'News',          'slug' => 'news',          'color' => '#7c3aed'],
            ['name' => 'Sim Racing',    'slug' => 'sim-racing',    'color' => '#2563eb'],
            ['name' => 'Announcements', 'slug' => 'announcements', 'color' => '#db2777'],
            ['name' => 'Real Racing',   'slug' => 'real-racing',   'color' => '#16a34a'],
            ['name' => 'GT3',           'slug' => 'gt3',           'color' => '#ea580c'],
            ['name' => 'GT4',           'slug' => 'gt4',           'color' => '#d97706'],
            ['name' => 'Endurance',     'slug' => 'endurance',     'color' => '#0891b2'],
            ['name' => 'Formula',       'slug' => 'formula',       'color' => '#dc2626'],
            ['name' => 'LMU',           'slug' => 'lmu',           'color' => '#7c3aed'],
            ['name' => 'ACC',           'slug' => 'acc',           'color' => '#1d4ed8'],
            ['name' => 'iRacing',       'slug' => 'iracing',       'color' => '#15803d'],
        ];

        foreach ($tags as $tag) {
            NewsTag::firstOrCreate(['slug' => $tag['slug']], $tag);
        }
    }
}
