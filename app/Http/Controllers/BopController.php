<?php

namespace App\Http\Controllers;

use App\Models\Bop;

class BopController extends Controller
{
    public function index()
    {
        $games      = Bop::games();
        $categories = Bop::categories();

        $activeGame = request('game', 'acc');
        if (!array_key_exists($activeGame, $games)) {
            $activeGame = 'acc';
        }

        $activeCategory = request('category');
        if ($activeCategory && !array_key_exists($activeCategory, $categories)) {
            $activeCategory = null;
        }

        $tracks = Bop::where('game', $activeGame)
            ->whereNotNull('track')
            ->distinct()
            ->orderBy('track')
            ->pluck('track');

        $activeTrack = request('track');
        if ($activeTrack && !$tracks->contains($activeTrack)) {
            $activeTrack = null;
        }

        $query = Bop::where('game', $activeGame);

        if ($activeCategory) {
            $query->whereIn('car_model', Bop::carNamesByCategory($activeCategory));
        }

        if ($activeTrack) {
            $query->where(function ($q) use ($activeTrack) {
                $q->where('track', $activeTrack)->orWhereNull('track');
            });
        }

        $bops = $query->orderBy('track')->orderBy('car_model')->get();

        return view('bop.index', compact('games', 'categories', 'activeGame', 'activeCategory', 'tracks', 'activeTrack', 'bops'));
    }
}