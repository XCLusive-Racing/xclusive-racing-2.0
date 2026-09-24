<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// ACC PC ('ac') is identical to ACC console ('acc') for event-format purposes
// (same session/pitstop rules) — only BOP car model IDs differ between the
// two builds, which is a separate concern (Bop::carModels()). Duplicate every
// console format under the 'ac' game key so the "ACC PC" bucket on
// admin/event-formats isn't empty.
return new class extends Migration
{
    public function up(): void
    {
        $formats = DB::table('event_formats')->where('game', 'acc')->get();

        foreach ($formats as $format) {
            $row = (array) $format;
            unset($row['id']);
            $row['game'] = 'ac';
            $row['created_at'] = now();
            $row['updated_at'] = now();

            $exists = DB::table('event_formats')
                ->where('game', 'ac')
                ->where('name', $row['name'])
                ->exists();

            if (! $exists) {
                DB::table('event_formats')->insert($row);
            }
        }
    }

    public function down(): void
    {
        DB::table('event_formats')->where('game', 'ac')->delete();
    }
};
