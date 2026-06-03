<?php

namespace App\Http\Controllers;

use App\Models\Hotlap;

class HotlapController extends Controller
{
    public function index()
    {
        $hotlaps = Hotlap::with('driver')
            ->orderBy('best_lap')
            ->paginate(50);

        return view('hotlaps.index', compact('hotlaps'));
    }
}