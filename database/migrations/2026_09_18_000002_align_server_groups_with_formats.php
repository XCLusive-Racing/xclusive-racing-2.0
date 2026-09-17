<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Sets EventFormat.server_group so the existing auto-server-select JS
// (admin/races/form.blade.php's recomputeServer(), mirrored in
// resources/js/pages/admin/import-export.js's autoFillRow()) actually fires. The
// column has existed since 2026_08_16_174309_add_server_group_to_event_formats_table
// but was never populated by EventFormatSeeder or any migration, so every event
// manager has had to pick a server by hand despite the auto-fill already being built.
//
// User-specified structure, 2026-09:
//   short  -> Server 1 (resets every 1h, starting :00), any hour
//   medium -> Server 2 (resets every 2h, starting :00) on an even scheduled hour,
//             Server 3 (resets every 2h, starting :01) on an odd one
//   long   -> Server 4 (scheduled/manual restart), any hour
// See resources/views/admin/races/form.blade.php's serverNumberFor() for exactly how
// a group maps to a server number/hour parity — this migration only sets the group.
//
// Same 9-name format roster as 2026_09_18_000001_align_event_tags_with_formats.php
// (Double Sprint/Double Race are not part of the current active roster, left as-is).
return new class extends Migration
{
    public function up(): void
    {
        $groups = [
            'Super Sprint'      => 'short',
            'Sprint Race'       => 'short',
            'Daily Race'        => 'medium',
            'Intermediate Race' => 'medium',
            'Full Race'         => 'medium',
            'Multiclass'        => 'medium',
            'Long Race'         => 'long',
            'Mini Enduro'       => 'long',
            'Endurance'         => 'long',
        ];

        foreach ($groups as $formatName => $group) {
            DB::table('event_formats')->where('name', $formatName)->update(['server_group' => $group]);
        }
    }

    public function down(): void
    {
        DB::table('event_formats')->whereIn('name', [
            'Super Sprint', 'Sprint Race', 'Daily Race', 'Intermediate Race',
            'Full Race', 'Multiclass', 'Long Race', 'Mini Enduro', 'Endurance',
        ])->update(['server_group' => null]);
    }
};
