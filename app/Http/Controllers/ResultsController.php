<?php

namespace App\Http\Controllers;

use App\Models\Race;

class ResultsController extends Controller
{
    public function index()
    {
        $races = Race::where('status', 'finished')
            ->orderBy('scheduled_at', 'desc')
            ->get();

        $selected = $races->firstWhere('id', request('race')) ?? $races->first();

        $raceResults  = $selected?->raceResults()->with('user')->get() ?? collect();
        $qualiResults = $selected?->qualiResults()->with('user')->get() ?? collect();

        return view('results.index', compact('races', 'selected', 'raceResults', 'qualiResults'));
    }
}