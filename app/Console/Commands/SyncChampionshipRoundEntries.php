<?php

namespace App\Console\Commands;

use App\Models\Championship;
use App\Services\ChampionshipRoundEntryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

// Enters every approved championship entry into every upcoming round it isn't in
// yet — the same as the "Enter all into upcoming rounds" button on a championship's
// Entries page, for all championships at once. Run once after deploying automatic
// round entry, so entries from before it catch up; safe to run again any time.
class SyncChampionshipRoundEntries extends Command
{
    protected $signature = 'championships:sync-round-entries
                            {championship? : Only this championship (id)}
                            {--dry-run : Show what would be added without adding it}';

    protected $description = 'Enter every championship entry into every upcoming round it is not in yet';

    public function handle(ChampionshipRoundEntryService $rounds): int
    {
        $championships = Championship::withoutTenantScope()
            ->when($this->argument('championship'), fn ($query, $id) => $query->whereKey($id))
            ->whereIn('status', ['published', 'registration_open', 'registration_closed', 'running'])
            ->get();

        foreach ($championships as $championship) {
            // A dry run does the real work and rolls it back, so its count is exact.
            DB::beginTransaction();
            $added = $rounds->syncChampionship($championship);
            $this->option('dry-run') ? DB::rollBack() : DB::commit();

            $this->line("{$championship->id} {$championship->name}: ".($this->option('dry-run') ? 'would add ' : 'added ').$added);
        }

        return self::SUCCESS;
    }
}
