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
                'driver_positions' => ['required', 'array'],
                'driver_points' => ['nullable', 'array'],
            ], [
                'driver_positions.required' => 'Select at least one driver and enter their position.',
            ]);

            $driverPositions = array_filter(
                $validated['driver_positions'],
                fn ($pos) => trim((string) $pos) !== ''
            );

            if (empty($driverPositions)) {
                throw ValidationException::withMessages([
                    'driver_positions' => 'Select at least one driver and enter their position.',
                ]);
            }

            $validDriverIds = EsportsDriver::whereIn('id', array_keys($driverPositions))->pluck('id')->all();
            $driverPositions = array_intersect_key($driverPositions, array_flip($validDriverIds));
            $driverPoints = $validated['driver_points'] ?? [];
        }

        DB::transaction(function () use ($data, $isPro, $validated, $existing, $driverPositions, $driverPoints) {
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
