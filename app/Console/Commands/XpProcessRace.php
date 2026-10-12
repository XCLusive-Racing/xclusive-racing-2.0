<?php

namespace App\Console\Commands;

use App\Jobs\AwardRaceXp;
use App\Models\Race;
use Illuminate\Console\Command;

// Queues the XP jobs of one race by hand: for a race whose jobs failed, or one imported
// while XP was switched off. Not a backfill: one race at a time, and safe to repeat.
class XpProcessRace extends Command
{
    protected $signature = 'xp:process-race {race : The race ID}';

    protected $description = 'Queue the XP award jobs for every driver of one race';

    public function handle(): int
    {
        if (! config('xp.enabled')) {
            $this->error('XP is switched off (XP_ENABLED). Nothing queued.');

            return self::FAILURE;
        }

        $race = Race::find($this->argument('race'));
        if (! $race) {
            $this->error('Race not found.');

            return self::FAILURE;
        }

        $this->info(AwardRaceXp::dispatchForRace($race).' job(s) queued for "'.$race->title.'".');

        return self::SUCCESS;
    }
}
