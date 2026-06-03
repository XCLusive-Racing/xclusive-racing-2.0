<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    public function index(Request $request)
    {
        $query = Driver::with('stats')->orderByDesc('xcl_rating');

        if ($request->filled('q')) {
            $query->where('gamertag', 'like', '%' . $request->q . '%');
        }

        $drivers = $query->paginate(50)->withQueryString();

        return view('drivers.index', compact('drivers'));
    }

    public function show(Driver $driver)
    {
        $driver->load(['stats', 'trackTimes', 'hotlaps']);

        $trackTimes = $driver->trackTimes
            ->sortBy('track')
            ->values();

        return view('drivers.show', compact('driver', 'trackTimes'));
    }
}