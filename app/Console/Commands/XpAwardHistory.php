<?php

namespace App\Console\Commands;

use App\Models\AchievementAward;
use App\Models\RaceResult;
use App\Models\User;
use App\Models\XpLedgerEntry;
use App\Xp\XpAwarder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

// Awards every driver the achievements their history at XCL already meets: results on
// the site, the imported pre-website totals, their ratings and their profile. No racing
// XP for old races: only achievements. Safe to repeat, an achievement is awarded once.
// Run it again after importing more history (xcl:import:legacystats).
class XpAwardHistory extends Command
{
    protected $signature = 'xp:award-history
        {--dry-run : Show what would be awarded and write nothing}
        {--user= : Only this driver (user ID)}';

    protected $description = 'Award drivers the achievements their existing history already meets';

    public function handle(XpAwarder $awarder): int
    {
        if (! config('xp.enabled')) {
            $this->error('XP is switched off (XP_ENABLED). Nothing awarded.');

            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');
        $ids = $this->option('user')
            ? collect([(int) $this->option('user')])
            : User::where('is_filler', false)
                ->where(fn ($q) => $q->whereIn('id', RaceResult::whereNotNull('user_id')->select('user_id'))
                    ->orWhere('legacy_races', '>', 0))
                ->orderBy('id')->pluck('id');

        $rows = [];
        foreach ($ids as $id) {
            $user = User::find($id);
            if (! $user) {
                continue;
            }

            DB::beginTransaction();
            $before = (int) $user->xp_total;
            $awards = $awarder->evaluate($user);
            $awarder->refreshTotals($user);
            $gained = (int) XpLedgerEntry::where('user_id', $id)->sum('xp') - $before;
            $level = (int) User::whereKey($id)->value('xp_rank');
            $dry ? DB::rollBack() : DB::commit();

            if ($awards > 0) {
                $rows[] = [$id, $user->name, $awards, number_format($gained), $level];
            }
        }

        usort($rows, fn ($a, $b) => $b[2] <=> $a[2]);
        $this->table(['ID', 'Driver', 'Achievements', 'XP', 'Level after'], array_slice($rows, 0, 25));
        $this->info(($dry ? 'Dry run, nothing written. ' : '').count($rows).' of '.$ids->count().' drivers '
            .($dry ? 'would receive ' : 'received ').array_sum(array_column($rows, 2)).' achievements.');

        if (! $dry) {
            $this->line('Held in total: '.AchievementAward::count().' achievements.');
        }

        return self::SUCCESS;
    }
}
