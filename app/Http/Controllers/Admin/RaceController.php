<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Race;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RaceController extends Controller
{
    public function index()
    {
        // Auto-close races whose start time has passed but are still open
        Race::where('status', 'open')
            ->where('scheduled_at', '<', now())
            ->update(['status' => 'closed']);

        $races = Race::withCount('registrations')
            ->orderBy('scheduled_at', 'desc')
            ->get();

        $stats = [
            'total'         => $races->count(),
            'open'          => $races->where('status', 'open')->count(),
            'finished'      => $races->where('status', 'finished')->count(),
            'registrations' => $races->sum('registrations_count'),
        ];

        return view('admin.races.index', compact('races', 'stats'));
    }

    public function create(Request $request)
    {
        $prefillDate = $request->date('date')?->format('Y-m-d\TH:i');
        return view('admin.races.create', compact('prefillDate'));
    }

    public function bulkCreate()
    {
        return view('admin.races.bulk_create');
    }

    public function bulkStore(Request $request)
    {
        $data = $request->validate([
            'title'          => 'required|string|max:200',
            'game'           => 'required|in:acc,lmu,iracing',
            'track'          => 'required|string|max:255',
            'start_date'     => 'required|date',
            'start_time'     => 'required|date_format:H:i',
            'rounds'         => 'required|integer|min:1|max:52',
            'interval_weeks' => 'required|integer|min:1|max:4',
            'max_drivers'    => 'nullable|integer|min:1',
            'description'    => 'nullable|string',
        ]);

        $base = Carbon::parse($data['start_date'] . ' ' . $data['start_time']);
        $numberRounds = $request->boolean('number_rounds');

        for ($i = 0; $i < (int) $data['rounds']; $i++) {
            Race::create([
                'title'        => $numberRounds ? $data['title'] . ' — R' . ($i + 1) : $data['title'],
                'game'         => $data['game'],
                'track'        => $data['track'],
                'scheduled_at' => $base->copy()->addWeeks($i * (int) $data['interval_weeks']),
                'max_drivers'  => $data['max_drivers'] ?? null,
                'description'  => $data['description'] ?? null,
                'status'       => 'open',
            ]);
        }

        return redirect()->route('admin.calendar')
            ->with('success', $data['rounds'] . ' races scheduled successfully!');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'game'         => 'required|in:acc,lmu,iracing',
            'track'        => 'required|string|max:255',
            'scheduled_at' => 'required|date',
            'max_drivers'  => 'nullable|integer|min:1',
            'description'  => 'nullable|string',
        ]);

        Race::create($data);

        return redirect()->route('admin.races.index')->with('success', 'Race created successfully!');
    }

    public function edit(Race $race)
    {
        if ($race->isPast()) {
            return redirect()->route('admin.races.index')
                ->with('error', 'Past races cannot be edited. You can still manage results.');
        }

        return view('admin.races.edit', compact('race'));
    }

    public function update(Request $request, Race $race)
    {
        if ($race->isPast()) {
            return redirect()->route('admin.races.index')
                ->with('error', 'Past races cannot be edited.');
        }
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'game'         => 'required|in:acc,lmu,iracing',
            'track'        => 'required|string|max:255',
            'scheduled_at' => 'required|date',
            'status'       => 'required|in:open,closed,finished',
            'max_drivers'  => 'nullable|integer|min:1',
            'description'  => 'nullable|string',
        ]);

        $race->update($data);

        return redirect()->route('admin.races.index')->with('success', 'Race updated successfully!');
    }
}