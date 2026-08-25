<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('esports_drivers')
            ->where('name', 'Brody Laweless')
            ->update([
                'name' => 'Brody Lawless',
                'slug' => 'brody-lawless',
                'photo' => '/images/drivers/brodylaweless.png',
                'socials' => json_encode([
                    ['type' => 'tiktok', 'href' => 'https://www.tiktok.com/@brodyl00'],
                    ['type' => 'instagram', 'href' => 'https://www.instagram.com/brody.l1/'],
                ]),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('esports_drivers')
            ->where('name', 'Brody Lawless')
            ->update([
                'name' => 'Brody Laweless',
                'slug' => 'brody-laweless',
                'photo' => null,
                'socials' => json_encode([]),
                'updated_at' => now(),
            ]);
    }
};
