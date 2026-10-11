<?php

namespace App\Services;

use App\Models\RaceResult;
use App\Models\Report;
use App\Models\TimeTrialEvent;
use App\Models\TimeTrialEventResult;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

// Everything that moved a driver's XCL Rating or Safety Rating, newest first, for the
// profile page's Rating Changes tab: race results, steward penalties against them, rating
// returned to them after a report they filed, and Time Trial points.
class RatingHistory
{
    public const LIMIT = 100;

    /** @return Collection<int, array{at: ?Carbon, type: string, title: string, detail: ?string, game: ?string, elo: ?float, sr: ?float}> */
    public function for(User $user): Collection
    {
        return collect()
            ->concat($this->races($user))
            ->concat($this->penalties($user))
            ->concat($this->returns($user))
            ->concat($this->timeTrials($user))
            ->sortByDesc(fn (array $entry) => $entry['at']?->getTimestamp() ?? 0)
            ->take(self::LIMIT)
            ->values();
    }

    private function races(User $user): Collection
    {
        return RaceResult::where('user_id', $user->id)
            ->where('session_type', 'race')
            ->where(fn ($q) => $q->whereNotNull('elo_change')->orWhereNotNull('sr_change'))
            ->get()
            ->map(fn (RaceResult $result) => [
                'at' => $result->race_scheduled_at ? Carbon::parse($result->race_scheduled_at) : $result->created_at,
                'type' => 'race',
                'title' => ($result->race_title ?? 'Race').((int) $result->race_number > 1 ? ' — Race '.$result->race_number : ''),
                'detail' => $result->race_track,
                'game' => $result->race_game,
                'elo' => $result->elo_change !== null ? (float) $result->elo_change : null,
                'sr' => $result->sr_change !== null ? (float) $result->sr_change : null,
            ]);
    }

    // Processed reports against this driver that cost rating (Admin\ReportController::process()).
    private function penalties(User $user): Collection
    {
        return $this->processedReports()
            ->where('reported_user_id', $user->id)
            ->get()
            ->filter(fn (Report $report) => $report->appliesRating())
            ->map(fn (Report $report) => [
                'at' => $report->processed_at,
                'type' => 'penalty',
                'title' => $report->race?->title ?? 'Race',
                'detail' => 'Penalty: '.$report->final_penalty,
                'game' => $report->race?->game,
                'elo' => -(float) $report->xcl_rating_deduction,
                'sr' => -(float) $report->sr_deduction,
            ]);
    }

    // The reporter of a race incident gets rating back when the other driver is penalised.
    private function returns(User $user): Collection
    {
        return $this->processedReports()
            ->where('user_id', $user->id)
            ->where('session_type', 'R')
            ->where('xcl_rating_return', '>', 0)
            ->get()
            ->filter(fn (Report $report) => $report->appliesRating())
            ->map(fn (Report $report) => [
                'at' => $report->processed_at,
                'type' => 'return',
                'title' => $report->race?->title ?? 'Race',
                'detail' => 'Rating returned after your report',
                'game' => $report->race?->game,
                'elo' => (float) $report->xcl_rating_return,
                'sr' => null,
            ]);
    }

    private function processedReports()
    {
        return Report::where('status', 'resolved')
            ->whereNotNull('processed_at')
            ->with(['race.championship' => fn ($q) => $q->withoutTenantScope()]);
    }

    private function timeTrials(User $user): Collection
    {
        $results = TimeTrialEventResult::where('user_id', $user->id)->where('rating_change', '!=', 0)->get();
        $events = TimeTrialEvent::whereIn('id', $results->pluck('time_trial_event_id'))->get()->keyBy('id');

        return $results->map(function (TimeTrialEventResult $result) use ($events) {
            $event = $events->get($result->time_trial_event_id);

            return [
                'at' => $event?->finalized_at ?? $result->created_at,
                'type' => 'time_trial',
                'title' => $event?->title ?: (($event ? $event->trackName().' ' : '').'Time Trial'),
                'detail' => 'P'.$result->position,
                'game' => 'acc',
                'elo' => (float) $result->rating_change,
                'sr' => null,
            ];
        });
    }
}
