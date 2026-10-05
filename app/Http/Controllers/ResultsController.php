<?php

namespace App\Http\Controllers;

use App\Models\Race;
use App\Models\RaceSessionFile;
use App\Services\AccResultsParser;
use Illuminate\Support\Facades\Storage;

class ResultsController extends Controller
{
    public function index()
    {
        // Only races that actually have results: a finished race nobody drove (no
        // qualifying or race rows) isn't listed at all instead of showing "no results".
        $races = Race::select(['id', 'title', 'game', 'track', 'scheduled_at', 'status'])
            ->where('status', 'finished')
            ->whereExists(fn ($q) => $q->from('race_results')->whereColumn('race_results.race_id', 'races.id'))
            ->orderBy('scheduled_at', 'desc')
            ->get();

        $selectedId = $races->contains('id', (int) request('race')) ? (int) request('race') : $races->first()?->id;
        $selected = $selectedId ? Race::with(['eventFormat', 'raceClasses', 'teamEntries'])->find($selectedId) : null;

        // A multi-race round shows one race at a time (?race_number=2), each with its
        // own classification, rating changes and stats file.
        $raceNumbers = $selected?->raceResults()->reorder()->distinct()->orderBy('race_number')->pluck('race_number')->map(fn ($n) => (int) $n) ?? collect();
        $raceNumber = $raceNumbers->contains((int) request('race_number')) ? (int) request('race_number') : ($raceNumbers->first() ?? 1);

        $raceResults = $selected?->raceResults()->where('race_number', $raceNumber)->with('user')->get() ?? collect();
        $qualiResults = $selected?->qualiResults()->with('user')->get() ?? collect();

        // Detailed stats (laps, sectors, consistency...) from the race's raw results file —
        // in the database (RaceSessionFile), or a local file left from before that. They're a
        // membership perk: only built for a supporter, everyone else gets the teaser.
        $stats = null;
        $statsAvailable = false;
        $canSeeStats = (bool) auth()->user()?->hasTier('supporter');
        if ($selected) {
            $json = RaceSessionFile::jsonFor($selected, $raceNumber);
            $legacyPath = $raceNumber > 1 ? $selected->resultsJsonPath($raceNumber) : $selected->results_json_path;
            if ($json === null && $legacyPath && Storage::disk('local')->exists($legacyPath)) {
                $json = Storage::disk('local')->get($legacyPath);
            }
            $statsAvailable = $json !== null;
            if ($statsAvailable && $canSeeStats) {
                $stats = (new AccResultsParser)->parseContent($json, $selected->game);
            }
        }

        return view('results.index', compact('races', 'selected', 'raceResults', 'qualiResults', 'stats', 'statsAvailable', 'canSeeStats', 'raceNumbers', 'raceNumber'));
    }
}
