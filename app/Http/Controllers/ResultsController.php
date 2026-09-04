<?php

namespace App\Http\Controllers;

use App\Models\Race;
use App\Services\AccResultsParser;
use Illuminate\Support\Facades\Storage;

class ResultsController extends Controller
{
    public function index()
    {
        $races = Race::select(['id','title','game','track','scheduled_at','status'])
            ->where('status', 'finished')
            ->orderBy('scheduled_at', 'desc')
            ->get();

        $selectedId = request('race') ?? $races->first()?->id;
        $selected   = $selectedId ? Race::with(['eventFormat', 'raceClasses', 'teamEntries'])->find($selectedId) : null;

        $raceResults  = $selected?->raceResults()->with('user')->get() ?? collect();
        $qualiResults = $selected?->qualiResults()->with('user')->get() ?? collect();

        $stats = null;
        if ($selected?->results_json_path && Storage::disk('local')->exists($selected->results_json_path)) {
            $stats = (new AccResultsParser())->parse(Storage::disk('local')->path($selected->results_json_path));
        }

        return view('results.index', compact('races', 'selected', 'raceResults', 'qualiResults', 'stats'));
    }
}