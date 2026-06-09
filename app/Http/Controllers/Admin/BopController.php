<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bop;
use Illuminate\Http\Request;

class BopController extends Controller
{
    public function index()
    {
        $bops = Bop::orderBy('game')->orderBy('car_model')->get()->groupBy('game');
        $games = Bop::games();
        return view('admin.bops.index', compact('bops', 'games'));
    }

    public function create()
    {
        $games = Bop::games();
        return view('admin.bops.create', compact('games'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'game'       => 'required|in:acc,lmu,iracing,ac',
            'car_model'  => 'required|string|max:100',
            'track'      => 'nullable|string|max:100',
            'ballast_kg' => 'required|integer|min:-100|max:200',
            'restrictor' => 'required|integer|min:0|max:20',
            'notes'      => 'nullable|string|max:500',
        ]);

        Bop::create($request->only('game', 'car_model', 'track', 'ballast_kg', 'restrictor', 'notes'));

        return redirect()->route('admin.bops.index')->with('success', 'BOP entry created.');
    }

    public function edit(Bop $bop)
    {
        $games = Bop::games();
        return view('admin.bops.edit', compact('bop', 'games'));
    }

    public function update(Request $request, Bop $bop)
    {
        $request->validate([
            'game'       => 'required|in:acc,lmu,iracing,ac',
            'car_model'  => 'required|string|max:100',
            'track'      => 'nullable|string|max:100',
            'ballast_kg' => 'required|integer|min:-100|max:200',
            'restrictor' => 'required|integer|min:0|max:20',
            'notes'      => 'nullable|string|max:500',
        ]);

        $bop->update($request->only('game', 'car_model', 'track', 'ballast_kg', 'restrictor', 'notes'));

        return redirect()->route('admin.bops.index')->with('success', 'BOP entry updated.');
    }

    public function destroy(Bop $bop)
    {
        $bop->delete();
        return redirect()->route('admin.bops.index')->with('success', 'BOP entry deleted.');
    }

    private static array $accCarNames = [
        0  => 'Porsche 991 GT3 R',
        1  => 'Mercedes-AMG GT3',
        2  => 'Ferrari 488 GT3',
        3  => 'Audi R8 LMS',
        4  => 'Lamborghini Huracán GT3',
        5  => 'McLaren 650S GT3',
        6  => 'Nissan GT-R Nismo GT3 2018',
        7  => 'BMW M6 GT3',
        8  => 'Bentley Continental GT3 2018',
        9  => 'Porsche 991 II GT3 Cup',
        10 => 'Nissan GT-R Nismo GT3 2017',
        11 => 'Bentley Continental GT3 2016',
        12 => 'Aston Martin Vantage V12 GT3',
        13 => 'Lamborghini Gallardo R-EX',
        14 => 'Jaguar G3',
        15 => 'Lexus RC F GT3',
        16 => 'Lamborghini Huracán GT3 Evo',
        17 => 'Honda NSX GT3',
        18 => 'Lamborghini Huracán SuperTrofeo',
        19 => 'Audi R8 LMS Evo',
        20 => 'Aston Martin AMR V8 Vantage GT3',
        21 => 'Honda NSX GT3 Evo',
        22 => 'McLaren 720S GT3',
        23 => 'Porsche 991 II GT3 R',
        24 => 'Ferrari 488 GT3 Evo',
        25 => 'Mercedes-AMG GT3 2020',
        26 => 'Ferrari 488 Challenge Evo',
        27 => 'BMW M2 Club Sport Racing',
        28 => 'Porsche 992 GT3 Cup',
        29 => 'Lamborghini Huracán SuperTrofeo EVO2',
        30 => 'BMW M4 GT3',
        31 => 'Audi R8 LMS GT3 Evo 2',
        32 => 'Ferrari 296 GT3',
        33 => 'Lamborghini Huracán GT3 Evo 2',
        34 => 'Porsche 992 GT3 R',
        35 => 'McLaren 720S GT3 Evo',
        36 => 'Ford Mustang GT3',
        50 => 'Alpine A110 GT4',
        51 => 'Aston Martin Vantage GT4',
        52 => 'Audi R8 LMS GT4',
        53 => 'BMW M4 GT4',
        55 => 'Chevrolet Camaro GT4.R',
        56 => 'Ginetta G55 GT4',
        57 => 'KTM X-Bow GT4',
        58 => 'Maserati MC GT4',
        59 => 'McLaren 570S GT4',
        60 => 'Mercedes-AMG GT4',
        61 => 'Porsche 718 Cayman GT4 Clubsport MR',
        80 => 'Audi R8 LMS GT3 Evo (Cup)',
        82 => 'BMW M4 GT4 2021',
        83 => 'Audi R8 LMS GT4 Evo',
        84 => 'Ferrari 296 GT3 Evo',
        85 => 'McLaren 720S GT3 Evo 2',
        86 => 'Porsche 992 GT3 R Evo',
    ];

    public function import(Request $request)
    {
        $request->validate([
            'json_file' => 'required|file|mimes:json,txt|max:4096',
            'game'      => 'required|in:acc,lmu,iracing,ac',
            'mode'      => 'required|in:merge,replace',
        ]);

        $content = file_get_contents($request->file('json_file')->getRealPath());
        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->with('import_error', 'Invalid JSON: ' . json_last_error_msg());
        }

        $entries = isset($decoded['entries']) ? $decoded['entries'] : $decoded;

        if (!is_array($entries) || empty($entries)) {
            return back()->with('import_error', 'JSON must contain an array of BOP entries.');
        }

        set_time_limit(120);

        $game    = $request->input('game');
        $mode    = $request->input('mode');
        $now     = now();
        $rows    = [];
        $skipped = 0;

        foreach ($entries as $entry) {
            if (!is_array($entry)) { $skipped++; continue; }

            $carModel = $entry['car_model'] ?? $entry['carModel'] ?? null;

            if (is_int($carModel)) {
                $carModel = self::$accCarNames[$carModel] ?? ('Car #' . $carModel);
            }

            if (!$carModel) { $skipped++; continue; }

            $rows[] = [
                'game'       => $game,
                'car_model'  => $carModel,
                'track'      => ($entry['track'] ?? null) ?: null,
                'ballast_kg' => (int) ($entry['ballast_kg'] ?? $entry['ballastKg'] ?? 0),
                'restrictor' => (int) ($entry['restrictor'] ?? 0),
                'notes'      => ($entry['notes'] ?? null) ?: null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($mode === 'replace') {
            Bop::where('game', $game)->delete();
            $created = count($rows);
            $updated = 0;
        } else {
            // Merge: delete only rows that will be re-inserted, keep the rest
            $existingKeys = Bop::where('game', $game)
                ->get(['id', 'car_model', 'track'])
                ->keyBy(fn($b) => $b->car_model . '|' . ($b->track ?? ''));

            $idsToDelete = [];
            $newCount    = 0;

            foreach ($rows as $row) {
                $key = $row['car_model'] . '|' . ($row['track'] ?? '');
                if (isset($existingKeys[$key])) {
                    $idsToDelete[] = $existingKeys[$key]->id;
                } else {
                    $newCount++;
                }
            }

            if ($idsToDelete) {
                foreach (array_chunk($idsToDelete, 500) as $chunk) {
                    Bop::whereIn('id', $chunk)->delete();
                }
            }

            $updated = count($idsToDelete);
            $created = $newCount;
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            Bop::insert($chunk);
        }

        $parts = [];
        if ($created) $parts[] = "{$created} created";
        if ($updated) $parts[] = "{$updated} updated";
        if ($skipped) $parts[] = "{$skipped} skipped";

        return back()->with('success', 'Import complete: ' . implode(', ', $parts) . '.');
    }
}