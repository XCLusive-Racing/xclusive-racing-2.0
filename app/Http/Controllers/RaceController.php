<?php

namespace App\Http\Controllers;

use App\Models\EventTag;
use App\Models\Race;
use App\Models\RaceRegistration;

class RaceController extends Controller
{
    public function index()
    {
        $races = Race::select(['id','title','game','track','scheduled_at','status','is_championship','event_tag','max_drivers','duration_key','image','icon','description','sr_requirement','min_rating'])
            ->where('status', '!=', 'finished')
            ->orderBy('scheduled_at')
            ->get();
        $races->loadCount('registrations');

        $eventTags = EventTag::orderBy('name')->get();

        return view('race.index', compact('races', 'eventTags'));
    }

    public function show(Race $race)
    {
        $race->load(['registrations.user', 'raceResults.user']);
        $isRegistered = auth()->check() && $race->isRegistered(auth()->user());

        return view('race.show', compact('race', 'isRegistered'));
    }

    public function register(Race $race)
    {
        if ($race->status !== 'open') {
            return back()->with('error', 'Registration is closed for this race.');
        }

        if ($race->isFull()) {
            return back()->with('error', 'This race is full.');
        }

        if ($race->isRegistered(auth()->user())) {
            return back()->with('error', 'You are already registered for this race.');
        }

        RaceRegistration::create([
            'race_id' => $race->id,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'You have been registered for ' . $race->title . '!');
    }

    public function unregister(Race $race)
    {
        if ($race->status !== 'open') {
            return back()->with('error', 'You cannot unregister from a closed race.');
        }

        RaceRegistration::where('race_id', $race->id)
            ->where('user_id', auth()->id())
            ->delete();

        return back()->with('success', 'You have been unregistered from ' . $race->title . '.');
    }
}