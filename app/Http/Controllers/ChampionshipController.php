<?php

namespace App\Http\Controllers;

use App\Models\Championship;
use App\Models\ChampionshipRegistration;
use Illuminate\Http\Request;

class ChampionshipController extends Controller
{
    public function index()
    {
        $championships = Championship::withCount(['rounds', 'registrations'])
            ->whereIn('status', ['active', 'finished'])
            ->orderBy('season', 'desc')
            ->orderBy('name')
            ->get();

        return view('championships.index', compact('championships'));
    }

    public function show(Championship $championship)
    {
        $championship->load(['classes', 'registrations.user', 'registrations.championshipClass']);
        $rounds    = $championship->rounds()->where('status', '!=', 'draft')->orderBy('round_number')->get();
        $standings = $championship->computeStandings();

        return view('championships.show', compact('championship', 'rounds', 'standings'));
    }

    public function register(Request $request, Championship $championship)
    {
        $user = $request->user();

        if ($championship->status !== 'active' || !$championship->registration_open) {
            return back()->with('error', 'Registration is not open.');
        }

        if ($championship->isRegistered($user)) {
            return back()->with('error', 'You are already registered.');
        }

        if ($championship->isFull()) {
            return back()->with('error', 'Championship is full.');
        }

        $classId = null;
        if ($championship->is_multiclass) {
            $request->validate(['championship_class_id' => 'required|exists:championship_classes,id']);
            $classId = $request->championship_class_id;
        }

        ChampionshipRegistration::create([
            'championship_id'       => $championship->id,
            'user_id'               => $user->id,
            'championship_class_id' => $classId,
        ]);

        return back()->with('success', 'You have been registered for the championship!');
    }

    public function unregister(Championship $championship)
    {
        $user = request()->user();

        $championship->registrations()
            ->where('user_id', $user->id)
            ->delete();

        return back()->with('success', 'You have been unregistered from the championship.');
    }
}
