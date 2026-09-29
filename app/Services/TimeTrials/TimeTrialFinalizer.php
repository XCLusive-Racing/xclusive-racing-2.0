<?php

namespace App\Services\TimeTrials;

use App\Models\TimeTrialEvent;
use App\Models\TimeTrialEventLap;
use App\Models\TimeTrialEventResult;
use App\Models\TimeTrialLap;
use App\Models\User;
use App\Services\RatingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Closes a weekly event once its window (and the last session's result file) is over:
// stores the final classification, awards the rating points, and adds every counting lap
// (each driver's best per car) to the All Time Records. Runs once per event.
class TimeTrialFinalizer
{
    public function __construct(
        private readonly TimeTrialStandings $standings,
        private readonly RatingService $rating,
    ) {}

    // An event is final once the last session that started in its window has ended and its
    // result file has had time to be collected.
    public function isDue(TimeTrialEvent $event): bool
    {
        return $event->is_published && $event->finalized_at === null
            && now()->gte($event->ends_at->copy()->addMinutes(TimeTrialResultCollector::sessionLoopMinutes() + 30));
    }

    public function finalize(TimeTrialEvent $event): bool
    {
        $done = DB::transaction(function () use ($event) {
            $event = TimeTrialEvent::lockForUpdate()->find($event->id);
            if (! $event || $event->finalized_at !== null) {
                return false;
            }

            $classification = $this->standings->for($event);

            foreach ($classification as $row) {
                /** @var TimeTrialEventLap $lap */
                $lap = $row['lap'];
                $user = $lap->user_id ? User::find($lap->user_id) : null;

                TimeTrialEventResult::create([
                    'time_trial_event_id' => $event->id,
                    'user_id' => $user?->id,
                    'platform_identifier' => $lap->platform_identifier,
                    'driver_name' => $lap->driver_name,
                    'position' => $row['position'],
                    'car_id' => $lap->car_id,
                    'lap_time_ms' => $lap->lap_time_ms,
                    'rating_change' => $user ? $row['points'] : 0,
                    'rating_before' => $user ? (int) $user->elo_acc : null,
                ]);

                if ($user) {
                    $this->rating->applyManualAdjustment($user, User::eloColumn('acc'), $row['points']);
                }
            }

            $this->mergeIntoRecords($event);
            $event->update(['finalized_at' => now()]);

            return true;
        });

        if ($done) {
            TimeTrialLap::recomputePersonalBests([$event->track]);
            Log::info("Time Trial event #{$event->id} finalized");
        }

        return $done;
    }

    // Every counting lap, one per driver and car, becomes an all-time lap (source server).
    private function mergeIntoRecords(TimeTrialEvent $event): void
    {
        $laps = $event->laps()->orderBy('lap_time_ms')->orderBy('recorded_at')->orderBy('id')->get();
        $records = $this->standings->allTimeBests($event, $laps->pluck('platform_identifier')->unique()->all());
        $now = now();

        $rows = $laps
            ->filter(fn (TimeTrialEventLap $lap) => $this->standings->counts($event, $lap, $records))
            ->unique(fn (TimeTrialEventLap $lap) => $lap->platform_identifier.'|'.$lap->car_id)
            ->map(fn (TimeTrialEventLap $lap) => [
                'user_id' => $lap->user_id,
                'platform_identifier' => $lap->platform_identifier,
                'platform' => $lap->platform,
                'driver_name' => $lap->driver_name,
                'track' => $event->track,
                'car_id' => $lap->car_id,
                'car_class' => $lap->car_class,
                'lap_time_ms' => $lap->lap_time_ms,
                'sector1_ms' => $lap->sector1_ms,
                'sector2_ms' => $lap->sector2_ms,
                'sector3_ms' => $lap->sector3_ms,
                'game_patch' => null,
                'source_event' => null,
                'laps_driven' => null,
                'source' => 'server',
                'time_trial_event_id' => $event->id,
                'is_personal_best' => false,
                'source_key' => sha1("event|{$event->id}|{$lap->platform_identifier}|{$lap->car_id}"),
                'recorded_at' => $lap->recorded_at,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();

        foreach (array_chunk($rows, 500) as $chunk) {
            TimeTrialLap::insertOrIgnore($chunk);
        }
    }
}
