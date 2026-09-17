<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Lines up event_tags with the fixed, short-to-long filter list on the public XCL Events
// page (resources/views/race/index.blade.php) and with every ACC EventFormat, so a
// format's own default_event_tag always exactly matches a real filter slug. Once this is
// in, event_tag never needs picking by hand — it's derived from the chosen format.
return new class extends Migration
{
    public function up(): void
    {
        // Rename existing tags' slugs (and match their color to the events-page filter)
        // rather than insert duplicates for the ones that already exist under a
        // differently-punctuated slug.
        $renames = [
            'full-race' => ['slug' => 'fullrace', 'color' => '#0d9488'],
            'long-race' => ['slug' => 'longrace', 'color' => '#4338ca'],
        ];
        foreach ($renames as $oldSlug => $attrs) {
            DB::table('event_tags')->where('slug', $oldSlug)->update(array_merge($attrs, ['updated_at' => now()]));
        }

        $recolors = [
            'sprint'       => '#f97316',
            'daily'        => '#eab308',
            'intermediate' => '#16a34a',
            'multiclass'   => '#0ea5e9',
            'endurance'    => '#9d174d',
        ];
        foreach ($recolors as $slug => $color) {
            DB::table('event_tags')->where('slug', $slug)->update(['color' => $color, 'updated_at' => now()]);
        }

        // Tags the filter list needs that don't exist under any slug yet.
        $missing = [
            ['name' => 'Super Sprint', 'slug' => 'supersprint', 'color' => '#dc2626'],
            ['name' => 'Mini Enduro',  'slug' => 'mini-enduro', 'color' => '#7c3aed'],
        ];
        foreach ($missing as $tag) {
            DB::table('event_tags')->insertOrIgnore(array_merge($tag, ['created_at' => now(), 'updated_at' => now()]));
        }

        // One-to-one: every ACC format now auto-fills the matching tag slug.
        $formatToTag = [
            'Super Sprint'      => 'supersprint',
            'Sprint Race'       => 'sprint',
            'Daily Race'        => 'daily',
            'Intermediate Race' => 'intermediate',
            'Full Race'         => 'fullrace',
            'Multiclass'        => 'multiclass',
            'Long Race'         => 'longrace',
            'Mini Enduro'       => 'mini-enduro',
            'Endurance'         => 'endurance',
        ];
        foreach ($formatToTag as $formatName => $tagSlug) {
            DB::table('event_formats')->where('name', $formatName)->update(['default_event_tag' => $tagSlug]);
        }
    }

    public function down(): void
    {
        DB::table('event_tags')->where('slug', 'fullrace')->update(['slug' => 'full-race']);
        DB::table('event_tags')->where('slug', 'longrace')->update(['slug' => 'long-race']);
        DB::table('event_tags')->whereIn('slug', ['supersprint', 'mini-enduro'])->delete();
        DB::table('event_formats')->update(['default_event_tag' => null]);
    }
};
