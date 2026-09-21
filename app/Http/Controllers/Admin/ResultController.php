<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EsportsDriver;
use App\Models\Result;
use App\Models\TeamEvent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ResultController extends Controller
{
    private const PRO_SUBJECTS = ['dirk-schouten', 'mats-van-rooijen'];

    public function index()
    {
        $results = Result::with(['races.positions.driver'])
            ->orderByDesc('year')
            ->orderByDesc('id')
            ->get();

        return view('admin.results.index', [
            'subjects' => TeamEvent::subjects(),
            'esportsDriversByGame' => $this->esportsDriversByGame(),
            'proResults' => $results->where('category', 'pro')->values(),
            'esportsResults' => $results->where('category', 'esports')->values(),
        ]);
    }

    public function store(Request $request)
    {
        $this->saveResult($request);

        return redirect()->route('admin.results.index')->with('success', 'Result created.');
    }

    public function edit(Result $result)
    {
        $result->load('races.positions');

        return view('admin.results.edit', [
            'result' => $result,
            'subjects' => TeamEvent::subjects(),
            'esportsDriversByGame' => $this->esportsDriversByGame(),
        ]);
    }

    public function update(Request $request, Result $result)
    {
        $this->saveResult($request, $result);

        return redirect()->route('admin.results.index')->with('success', 'Result updated.');
    }

    public function destroy(Result $result)
    {
        $result->delete();

        return back()->with('success', 'Result deleted.');
    }

    private function saveResult(Request $request, ?Result $existing = null): void
    {
        $subjects = array_keys(TeamEvent::subjects());

        $data = $request->validate([
            'subject' => ['required', Rule::in($subjects)],
        ]);

        $isPro = in_array($data['subject'], self::PRO_SUBJECTS, true);
        $driverPositions = [];
        $driverPoints = [];
        $driverCars = [];
        $driverCarNumbers = [];

        if ($isPro) {
            $validated = $request->validate([
                'year' => ['required', 'integer', 'min:2000', 'max:2100'],
                'title' => ['required', 'string', 'max:200'],
                'standing' => ['nullable', 'string', 'max:200'],
                'races' => ['required', 'array', 'min:1'],
                'races.*.track' => ['required', 'string', 'max:150'],
                'races.*.class' => ['nullable', 'string', 'max:100'],
                'races.*.positions' => ['required', 'string', 'max:200'],
            ]);
        } else {
            $type = $request->input('type', 'race');
            $isRace = $type === 'race';

            $validated = $request->validate([
                'type' => ['required', Rule::in(array_keys(Result::TYPES))],
                'event_date' => ['required', 'date'],
                // Standings/final results are named after the championship, so a title is required.
                'title' => [$isRace ? 'nullable' : 'required', 'string', 'max:200'],
                'round_label' => ['nullable', 'string', 'max:100'],
                'track' => [$isRace ? 'required' : 'nullable', 'string', 'max:150'],
                'car_class' => ['nullable', 'string', 'max:100'],
                'notes' => ['nullable', 'string', 'max:2000'],
                'selected_drivers' => ['required', 'array', 'min:1'],
                'selected_drivers.*' => ['integer'],
                'driver_positions' => ['nullable', 'array'],
                'driver_points' => ['nullable', 'array'],
                'driver_cars' => ['nullable', 'array'],
                'driver_cars.*' => ['nullable', 'string', 'max:150'],
                'driver_car_numbers' => ['nullable', 'array'],
                'driver_car_numbers.*' => ['nullable', 'string', 'max:20'],
            ], [
                'selected_drivers.required' => 'Select at least one driver who competed.',
                'selected_drivers.min' => 'Select at least one driver who competed.',
            ]);

            // The checkboxes are the source of truth for who competed. Hidden position
            // inputs are still submitted by the browser, so anything sent for an
            // unselected driver is discarded here.
            $selectedIds = array_values(array_unique(array_map('intval', $validated['selected_drivers'])));
            $names = EsportsDriver::whereIn('id', $selectedIds)->pluck('name', 'id');
            $selectedIds = array_values(array_filter($selectedIds, fn ($id) => $names->has($id)));

            if (empty($selectedIds)) {
                throw ValidationException::withMessages([
                    'selected_drivers' => 'Select at least one driver who competed.',
                ]);
            }

            $rawPositions = $validated['driver_positions'] ?? [];
            $missing = [];
            foreach ($selectedIds as $id) {
                $position = trim((string) ($rawPositions[$id] ?? ''));
                if ($position === '') {
                    $missing[] = $names[$id];
                } else {
                    $driverPositions[$id] = $position;
                }
            }

            if ($missing) {
                throw ValidationException::withMessages([
                    'driver_positions' => 'Enter a position for '.implode(', ', $missing).'.',
                ]);
            }

            // Car and car number only apply to a single race, not to standings/final tables.
            if ($isRace) {
                foreach ($selectedIds as $id) {
                    $car = trim((string) ($validated['driver_cars'][$id] ?? ''));
                    $number = trim((string) ($validated['driver_car_numbers'][$id] ?? ''));
                    $driverCars[$id] = $car !== '' ? $car : null;
                    $driverCarNumbers[$id] = $number !== '' ? $number : null;
                }
            }

            // Two drivers cannot share a classified position, unless they drove the same
            // car (same car number — insurance / driver swap), which shares the result.
            // Unclassified results (DNF, DNS and so on) can legitimately repeat, so they
            // are exempt.
            $unclassified = ['DNF', 'DNS', 'DSQ', 'DNQ', 'NC'];
            $seen = [];
            foreach ($driverPositions as $id => $position) {
                $key = strtoupper($position);
                if (in_array($key, $unclassified, true)) {
                    continue;
                }
                if (isset($seen[$key])) {
                    $first = $seen[$key];
                    $sameCar = $isRace
                        && ($driverCarNumbers[$id] ?? null) !== null
                        && strcasecmp($driverCarNumbers[$id], (string) ($driverCarNumbers[$first] ?? '')) === 0;

                    if (! $sameCar) {
                        throw ValidationException::withMessages([
                            'driver_positions' => $names[$first].' and '.$names[$id]." cannot both have position {$position} unless they share the same car number.",
                        ]);
                    }

                    continue;
                }
                $seen[$key] = $id;
            }

            $driverPoints = array_intersect_key($validated['driver_points'] ?? [], array_flip($selectedIds));
        }

        DB::transaction(function () use ($data, $isPro, $validated, $existing, $driverPositions, $driverPoints, $driverCars, $driverCarNumbers) {
            if ($isPro) {
                $header = [
                    'subject' => $data['subject'],
                    'category' => 'pro',
                    'type' => 'race',
                    'year' => $validated['year'],
                    'title' => $validated['title'],
                    'round_label' => null,
                    'standing' => ($validated['standing'] ?? '') ?: null,
                    'notes' => null,
                ];
            } else {
                $header = [
                    'subject' => $data['subject'],
                    'category' => 'esports',
                    'type' => $validated['type'],
                    'year' => (int) Carbon::parse($validated['event_date'])->format('Y'),
                    'title' => ($validated['title'] ?? '') ?: null,
                    'round_label' => $validated['type'] === 'standings' ? (($validated['round_label'] ?? '') ?: null) : null,
                    'notes' => ($validated['notes'] ?? '') ?: null,
                    'standing' => null,
                ];
            }

            $result = $existing ?? new Result;
            $result->fill($header);
            $result->save();

            // Replace races/positions wholesale on every save — simpler and safer
            // than diffing, and consistent with how the driver-picker's sync()
            // already fully replaces associations elsewhere in this codebase.
            if ($existing) {
                $result->races()->delete();
            }

            if ($isPro) {
                foreach ($validated['races'] as $raceIndex => $race) {
                    $resultRace = $result->races()->create([
                        'track' => $race['track'],
                        'car_class' => ($race['class'] ?? '') ?: null,
                        'sort_order' => $raceIndex,
                    ]);

                    $positions = preg_split('/[,\n]+/', $race['positions']);
                    $positions = array_values(array_filter(array_map('trim', $positions), fn ($p) => $p !== ''));

                    foreach ($positions as $posIndex => $position) {
                        $resultRace->positions()->create([
                            'position' => $position,
                            'sort_order' => $posIndex,
                        ]);
                    }
                }
            } else {
                $isRace = $validated['type'] === 'race';

                $resultRace = $result->races()->create([
                    'track' => $isRace ? $validated['track'] : null,
                    'car_class' => ($validated['car_class'] ?? '') ?: null,
                    'race_date' => $validated['event_date'],
                    'sort_order' => 0,
                ]);

                $posIndex = 0;
                foreach ($driverPositions as $driverId => $position) {
                    $points = $isRace ? '' : trim((string) ($driverPoints[$driverId] ?? ''));

                    $resultRace->positions()->create([
                        'esports_driver_id' => $driverId,
                        'position' => trim((string) $position),
                        'points' => $points !== '' ? $points : null,
                        'car' => $driverCars[$driverId] ?? null,
                        'car_number' => $driverCarNumbers[$driverId] ?? null,
                        'sort_order' => $posIndex++,
                    ]);
                }
            }
        });
    }

    private function esportsDriversByGame()
    {
        $drivers = EsportsDriver::orderBy('sort_order')->get()->groupBy('game');

        foreach (array_values(TeamEvent::teamSubjectGames()) as $game) {
            if (! $drivers->has($game)) {
                $drivers[$game] = collect();
            }
        }

        return $drivers;
    }
}
