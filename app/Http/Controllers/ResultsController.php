<?php

namespace App\Http\Controllers;

use App\Models\Race;
use App\Services\AccResultsParser;
use Illuminate\Support\Facades\Storage;

class ResultsController extends Controller
{
    public function index()
    {
        $races = Race::select(['id', 'title', 'game', 'track', 'scheduled_at', 'status'])
            ->where('status', 'finished')
            ->orderBy('scheduled_at', 'desc')
            ->get();

        $selectedId = request('race') ?? $races->first()?->id;
        $selected = $selectedId ? Race::with(['eventFormat', 'raceClasses', 'teamEntries'])->find($selectedId) : null;

        // A multi-race round shows one race at a time (?race_number=2), each with its
        // own classification, rating changes and stats file.
        $raceNumbers = $selected?->raceResults()->reorder()->distinct()->orderBy('race_number')->pluck('race_number')->map(fn ($n) => (int) $n) ?? collect();
        $raceNumber = $raceNumbers->contains((int) request('race_number')) ? (int) request('race_number') : ($raceNumbers->first() ?? 1);

        $raceResults = $selected?->raceResults()->where('race_number', $raceNumber)->with('user')->get() ?? collect();
        $qualiResults = $selected?->qualiResults()->with('user')->get() ?? collect();

        $stats = null;
        $statsPath = $raceNumber > 1 ? $selected?->resultsJsonPath($raceNumber) : $selected?->results_json_path;
        if ($statsPath && Storage::disk('local')->exists($statsPath)) {
            $stats = (new AccResultsParser)->parse(Storage::disk('local')->path($statsPath), $selected->game);
        }

        return view('results.index', compact('races', 'selected', 'raceResults', 'qualiResults', 'stats', 'raceNumbers', 'raceNumber'));
    }
}
