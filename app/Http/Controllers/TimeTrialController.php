<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Race;
use App\Models\TimeTrialCar;
use App\Models\TimeTrialEvent;
use App\Models\TimeTrialLap;
use App\Services\TimeTrials\TimeTrialStandings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

// Public Time Trials leaderboards. Every board (console, PC) is its own leaderboard and is
// never merged with another; a leaderboard row is a driver's personal best in one car.
class TimeTrialController extends Controller
{
    public function index(Request $request)
    {
        [$boards, $board] = $this->board($request);
        $platforms = $boards[$board]['platforms'];

        $stats = TimeTrialLap::whereIn('platform', $platforms)->where('is_personal_best', true)
            ->selectRaw('track, count(*) as entries, count(distinct platform_identifier) as drivers, min(lap_time_ms) as record_ms')
            ->groupBy('track');

        $records = TimeTrialLap::with('car')
            ->joinSub($stats, 'stats', fn ($join) => $join->on('time_trial_laps.track', '=', 'stats.track')
                ->on('time_trial_laps.lap_time_ms', '=', 'stats.record_ms'))
            ->whereIn('time_trial_laps.platform', $platforms)
            ->where('is_personal_best', true)
            ->orderBy('time_trial_laps.id')
            ->get(['time_trial_laps.*', 'stats.entries', 'stats.drivers'])
            ->unique('track');

        $tracks = $records
            ->map(fn (TimeTrialLap $record) => [
                'key' => $record->track,
                'name' => TimeTrialLap::trackName($record->track),
                'image' => $this->trackImage($record->track),
                'drivers' => (int) $record->drivers,
                'record' => $record,
            ])
            ->sortBy('name')
            ->values();

        // This week's event on top (live, else the next one), and the last finished one.
        $event = TimeTrialEvent::currentOrNext()->withCount('registrations')->first();
        $lastEvent = TimeTrialEvent::published()->whereNotNull('finalized_at')->latest('ends_at')->first();

        return view('time-trials.index', compact('boards', 'board', 'tracks', 'event', 'lastEvent'));
    }

    public function show(Request $request, string $track)
    {
        [$boards, $board] = $this->board($request);
        $platforms = $boards[$board]['platforms'];

        abort_unless(
            isset(TimeTrialLap::trackNames()[$track]) || TimeTrialLap::where('track', $track)->exists(),
            404
        );

        $onBoard = fn () => TimeTrialLap::where('track', $track)->whereIn('platform', $platforms);

        $classes = $onBoard()->distinct()->orderBy('car_class')->pluck('car_class');
        $class = $classes->contains($request->query('class')) ? $request->query('class') : null;

        $carIds = $onBoard()->when($class, fn ($q) => $q->where('car_class', $class))->distinct()->pluck('car_id');
        $cars = TimeTrialCar::whereIn('id', $carIds)->get()->sortBy(fn ($car) => $car->label())->values();
        // Car ID 0 is a real car (Porsche 991 GT3 R), so a missing ?car must not cast to 0.
        $carParam = (string) $request->query('car', '');
        $car = ctype_digit($carParam) ? $cars->firstWhere('id', (int) $carParam) : null;

        $filtered = fn () => $onBoard()
            ->when($class, fn ($q) => $q->where('car_class', $class))
            ->when($car, fn ($q) => $q->where('car_id', $car->id));

        $laps = $filtered()->where('is_personal_best', true)->with('car')
            ->orderBy('lap_time_ms')->orderBy('id')
            ->paginate(config('time_trials.per_page'))->withQueryString();

        $leaderMs = $filtered()->where('is_personal_best', true)->min('lap_time_ms');
        $theoretical = $this->theoreticalBest($filtered);

        // Public driver profiles are keyed on the platform identifier (drivers.xuid_psid).
        $profiles = Driver::whereIn('xuid_psid', $laps->pluck('platform_identifier')->unique())
            ->pluck('id', 'xuid_psid');

        return view('time-trials.show', [
            'boards' => $boards,
            'board' => $board,
            'track' => $track,
            'trackName' => TimeTrialLap::trackName($track),
            'trackImage' => $this->trackImage($track),
            'classes' => $classes,
            'class' => $class,
            'cars' => $cars,
            'car' => $car,
            'laps' => $laps,
            'leaderMs' => $leaderMs,
            'theoretical' => $theoretical,
            'profiles' => $profiles,
        ]);
    }

    // A weekly Time Trial event: signup, how to join, the driver's times to beat, and the
    // live classification (the stored final one once the event is finalized).
    public function event(Request $request, TimeTrialEvent $event, TimeTrialStandings $standings)
    {
        abort_unless($event->is_published, 404);

        $user = $request->user();
        $registered = $event->isRegistered($user);

        $finished = $event->finalized_at !== null;
        $leaderMs = $finished ? (int) $event->results()->min('lap_time_ms') : 0;
        $rows = $finished
            ? $event->results()->with('car')->get()->map(fn ($result) => [
                'position' => $result->position,
                'driver_name' => $result->driver_name,
                'identifier' => $result->platform_identifier,
                'car' => $result->car,
                'car_id' => $result->car_id,
                'lap_time_ms' => $result->lap_time_ms,
                'sectors' => null,
                'gap_ms' => $result->lap_time_ms - $leaderMs,
                'points' => $result->rating_change,
            ])
            : $standings->for($event)->map(fn ($row) => [
                'position' => $row['position'],
                'driver_name' => $row['lap']->driver_name,
                'identifier' => $row['lap']->platform_identifier,
                'car' => $row['lap']->car,
                'car_id' => $row['lap']->car_id,
                'lap_time_ms' => $row['lap']->lap_time_ms,
                'sectors' => [$row['lap']->sector1_ms, $row['lap']->sector2_ms, $row['lap']->sector3_ms],
                'gap_ms' => $row['gap_ms'],
                'points' => $row['points'],
            ]);

        // The signed-in driver's own all-time bests on this track: the times to beat, per car.
        $playerId = $user?->playerIdFor('acc');
        $timesToBeat = $playerId
            ? TimeTrialLap::with('car')->where('track', $event->track)->where('platform_identifier', $playerId)
                ->where('is_personal_best', true)
                ->when($event->car_class, fn ($q) => $q->where('car_class', $event->car_class))
                ->where(fn ($q) => $q->whereNull('time_trial_event_id')->orWhere('time_trial_event_id', '!=', $event->id))
                ->orderBy('lap_time_ms')->get()
            : collect();

        $profiles = Driver::whereIn('xuid_psid', collect($rows)->pluck('identifier')->unique())->pluck('id', 'xuid_psid');

        return view('time-trials.event', [
            'event' => $event->loadCount('registrations'),
            'registered' => $registered,
            'rows' => $rows,
            'finished' => $finished,
            'timesToBeat' => $timesToBeat,
            'hasPlayerId' => (bool) $playerId,
            'profiles' => $profiles,
            'trackImage' => $this->trackImage($event->track),
        ]);
    }

    public function register(Request $request, TimeTrialEvent $event): RedirectResponse
    {
        $user = $request->user();

        if (! $event->isOpenForSignup()) {
            return back()->with('error', 'Signups for this Time Trial are closed.');
        }
        if ($user->is_suspended) {
            return back()->with('error', 'Your account is suspended, so you cannot sign up right now.');
        }
        if (! $user->playerIdFor('acc')) {
            return back()->with('error', 'Add your Xbox or PlayStation account to your profile first, so the server knows you.');
        }

        $event->registrations()->firstOrCreate(['user_id' => $user->id]);
        $this->entryListChanged($event);

        return back()->with('success', $event->forced_entry_list
            ? 'You are signed up. The server gets the new entry list within a few minutes, your laps count from now on.'
            : 'You are signed up. Join the server any time, your laps count from now on.');
    }

    public function withdraw(Request $request, TimeTrialEvent $event): RedirectResponse
    {
        if ($event->finalized_at === null) {
            $event->registrations()->where('user_id', $request->user()->id)->delete();
            $this->entryListChanged($event);
        }

        return back()->with('success', 'You are no longer signed up for this Time Trial.');
    }

    // A forced entry list is the signups themselves: clearing last_pushed_at makes the
    // scheduler (time-trials:push-due, every 5 minutes) upload it again right away.
    private function entryListChanged(TimeTrialEvent $event): void
    {
        if ($event->forced_entry_list) {
            $event->update(['last_pushed_at' => null]);
        }
    }

    /** @return array{0: array, 1: string} enabled boards and the requested (or default) one */
    private function board(Request $request): array
    {
        $boards = TimeTrialLap::boards();
        $board = $request->query('platform');

        if (! isset($boards[$board])) {
            $board = isset($boards[config('time_trials.default_board')])
                ? config('time_trials.default_board')
                : array_key_first($boards);
        }

        return [$boards, $board];
    }

    // The sum of the fastest individual sectors set in any lap (not only personal bests)
    // under the current filters, with the lap each sector came from.
    private function theoreticalBest(callable $filtered): ?array
    {
        $sectors = [];
        foreach (['sector1_ms', 'sector2_ms', 'sector3_ms'] as $column) {
            $lap = $filtered()->whereNotNull($column)->orderBy($column)->orderBy('id')->first();
            if (! $lap) {
                return null;
            }
            $sectors[] = ['ms' => $lap->{$column}, 'driver' => $lap->driver_name];
        }

        return ['sectors' => $sectors, 'total' => array_sum(array_column($sectors, 'ms'))];
    }

    private function trackImage(string $track): ?string
    {
        $path = Race::trackImagePath(TimeTrialLap::trackName($track));

        return $path ? Storage::disk('media')->url($path) : null;
    }
}
