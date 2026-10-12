<?php

namespace App\Jobs;

use App\Models\Race;
use App\Models\RaceResult;
use App\Models\User;
use App\Xp\XpAwarder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// Awards one driver the XP of one race: racing XP, and any achievement the race tipped
// over (App\Xp\XpAwarder). Queued, one job per driver, once the race's results are in and
// its ratings have been processed. Running it again for the same race awards nothing new.
class AwardRaceXp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public int $raceId, public int $userId) {}

    // One job per driver with a linked result in the race. Does nothing while XP is
    // switched off (config xp.enabled).
    public static function dispatchForRace(Race $race): int
    {
        if (! config('xp.enabled')) {
            return 0;
        }

        $userIds = RaceResult::where('race_id', $race->id)->where('session_type', 'race')
            ->whereNotNull('user_id')->distinct()->pluck('user_id');

        $userIds->each(fn ($userId) => self::dispatch($race->id, (int) $userId));

        return $userIds->count();
    }

    public function handle(XpAwarder $awarder): void
    {
        if (! config('xp.enabled')) {
            return;
        }

        $race = Race::find($this->raceId);
        $user = User::find($this->userId);

        if ($race && $user) {
            $awarder->processRace($user, $race);
        }
    }
}
