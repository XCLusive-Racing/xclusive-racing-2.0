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

        $game = $request->input('game');

        if ($request->input('mode') === 'replace') {
            Bop::where('game', $game)->delete();
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($entries as $entry) {
            $carModel = $entry['car_model'] ?? null;

            if (!$carModel) {
                $skipped++;
                continue;
            }

            $track    = ($entry['track'] ?? null) ?: null;
            $ballast  = isset($entry['ballast_kg']) ? (int) $entry['ballast_kg'] : 0;
            $restrict = isset($entry['restrictor']) ? (int) $entry['restrictor'] : 0;
            $notes    = ($entry['notes'] ?? null) ?: null;

            if ($request->input('mode') === 'merge') {
                $existing = Bop::where('game', $game)
                    ->where('car_model', $carModel)
                    ->where('track', $track)
                    ->first();

                if ($existing) {
                    $existing->update([
                        'ballast_kg' => $ballast,
                        'restrictor' => $restrict,
                        'notes'      => $notes,
                    ]);
                    $updated++;
                    continue;
                }
            }

            Bop::create([
                'game'       => $game,
                'car_model'  => $carModel,
                'track'      => $track,
                'ballast_kg' => $ballast,
                'restrictor' => $restrict,
                'notes'      => $notes,
            ]);
            $created++;
        }

        $parts = [];
        if ($created) $parts[] = "{$created} created";
        if ($updated) $parts[] = "{$updated} updated";
        if ($skipped) $parts[] = "{$skipped} skipped";

        return back()->with('success', 'Import complete: ' . implode(', ', $parts) . '.');
    }
}