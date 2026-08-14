<?php

namespace App\Http\Controllers;

use App\Models\EsportsDriver;
use App\Models\TeamEvent;

class EsportsController extends Controller
{
    public function index()
    {
        $drivers = EsportsDriver::orderBy('sort_order')->get()->groupBy('game');

        $upcomingEventsByGame = [];

        foreach (['lmu', 'acc', 'iracing'] as $game) {
            if (! $drivers->has($game)) {
                $drivers[$game] = collect();
            }

            $driverIds = $drivers[$game]->pluck('id');

            $upcomingEventsByGame[$game] = TeamEvent::upcoming()
                ->whereHas('participatingDrivers', fn ($q) => $q->whereIn('esports_drivers.id', $driverIds))
                ->with('participatingDrivers')
                ->limit(2)
                ->get();
        }

        return view('teams.esports.index', compact('drivers', 'upcomingEventsByGame'));
    }

    public function show(EsportsDriver $driver)
    {
        $upcomingEvents = $driver->teamEvents()->upcoming()->limit(3)->get();

        return view('teams.esports.show', compact('driver', 'upcomingEvents'));
    }
}
