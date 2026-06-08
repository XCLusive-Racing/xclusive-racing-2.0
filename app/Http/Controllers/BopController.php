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

        $bops = Bop::where('game', $activeGame)
            ->orderBy('track')
            ->orderBy('car_model')
            ->get();

        return view('bop.index', compact('games', 'activeGame', 'bops'));
    }
}