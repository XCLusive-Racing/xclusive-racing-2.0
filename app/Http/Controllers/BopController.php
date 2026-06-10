<?php

namespace App\Http\Controllers;

use App\Models\Bop;

class BopController extends Controller
{
    public function index()
    {
        $games = Bop::games();

        $activeGame = request('game', 'acc');
        if (!array_key_exists($activeGame, $games)) {
            $activeGame = 'acc';
        }

        $tracks = Bop::where('game', $activeGame)
            ->whereNotNull('track')
            ->distinct()
            ->orderBy('track')
            ->pluck('track');

        $cars = Bop::where('game', $activeGame)
            ->distinct()
            ->orderBy('car_model')
            ->pluck('car_model');

        $activeTrack = request('track');
        if ($activeTrack && !$tracks->contains($activeTrack)) {
            $activeTrack = null;
        }

        $activeCar = request('car');
        if ($activeCar && !$cars->contains($activeCar)) {
            $activeCar = null;
        }

        $query = Bop::where('game', $activeGame);

        if ($activeTrack) {
            $query->where(function ($q) use ($activeTrack) {
                $q->where('track', $activeTrack)->orWhereNull('track');
            });
        }

        if ($activeCar) {
            $query->where('car_model', $activeCar);
        }

        $bops = $query->orderBy('track')->orderBy('car_model')->get();

        return view('bop.index', compact('games', 'activeGame', 'tracks', 'activeTrack', 'cars', 'activeCar', 'bops'));
    }
}