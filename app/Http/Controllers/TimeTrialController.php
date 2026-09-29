<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Race;
use App\Models\TimeTrialCar;
use App\Models\TimeTrialLap;
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

        return view('time-trials.index', compact('boards', 'board', 'tracks'));
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
        $car = $cars->firstWhere('id', (int) $request->query('car'));

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
