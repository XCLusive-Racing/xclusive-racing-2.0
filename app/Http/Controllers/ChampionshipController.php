<?php

namespace App\Http\Controllers;

use App\Models\Championship;
use App\Models\ChampionshipClass;
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
        $rounds         = $championship->rounds()->where('status', '!=', 'draft')->orderBy('round_number')->get();
        $standings      = $championship->computeStandings();
        $classStandings = $championship->computeClassStandings();

        return view('championships.show', compact('championship', 'rounds', 'standings', 'classStandings'));
    }

    public function register(Request $request, Championship $championship)
    {
        $user = $request->user();

        if ($user->isSuspended()) {
            return back()->with('error', 'Your account has been suspended. Please contact an administrator.');
        }

        if ($championship->status !== 'active' || !$championship->registration_open) {
            return back()->with('error', 'Registration is not open.');
        }

        if ($championship->isRegistered($user)) {
            return back()->with('error', 'You are already registered.');
        }

        if ($championship->isFull()) {
            return back()->with('error', 'Championship is full.');
        }

        if ($failure = $user->requirementFailure($championship->game, $championship->sr_requirement, $championship->min_rating)) {
            return back()->with('error', $failure);
        }

        $classId = null;
        if ($championship->is_multiclass) {
            $request->validate(['championship_class_id' => 'required|exists:championship_classes,id']);
            $classId = $request->championship_class_id;

            $class = ChampionshipClass::where('id', $classId)
                ->where('championship_id', $championship->id)
                ->firstOrFail();

            if ($failure = $user->requirementFailure($championship->game, $class->sr_requirement, $class->min_rating)) {
                return back()->with('error', $failure);
            }
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
