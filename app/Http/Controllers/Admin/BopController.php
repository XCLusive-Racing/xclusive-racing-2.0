<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bop;
use Illuminate\Http\Request;

class BopController extends Controller
{
    public function index()
    {
        $bops = Bop::orderBy('game')->orderBy('car_model')->get()->groupBy('game');
        $games = Bop::games();
        return view('admin.bops.index', compact('bops', 'games'));
    }

    public function create()
    {
        $games = Bop::games();
        return view('admin.bops.create', compact('games'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'game'       => 'required|in:acc,lmu,iracing,ac',
            'car_model'  => 'required|string|max:100',
            'track'      => 'nullable|string|max:100',
            'ballast_kg' => 'required|integer|min:-100|max:200',
            'restrictor' => 'required|integer|min:0|max:20',
            'notes'      => 'nullable|string|max:500',
        ]);

        Bop::create($request->only('game', 'car_model', 'track', 'ballast_kg', 'restrictor', 'notes'));

        return redirect()->route('admin.bops.index')->with('success', 'BOP entry created.');
    }

    public function edit(Bop $bop)
    {
        $games = Bop::games();
        return view('admin.bops.edit', compact('bop', 'games'));
    }

    public function update(Request $request, Bop $bop)
    {
        $request->validate([
            'game'       => 'required|in:acc,lmu,iracing,ac',
            'car_model'  => 'required|string|max:100',
            'track'      => 'nullable|string|max:100',
            'ballast_kg' => 'required|integer|min:-100|max:200',
            'restrictor' => 'required|integer|min:0|max:20',
            'notes'      => 'nullable|string|max:500',
        ]);

        $bop->update($request->only('game', 'car_model', 'track', 'ballast_kg', 'restrictor', 'notes'));

        return redirect()->route('admin.bops.index')->with('success', 'BOP entry updated.');
    }

    public function destroy(Bop $bop)
    {
        $bop->delete();
        return redirect()->route('admin.bops.index')->with('success', 'BOP entry deleted.');
    }
}