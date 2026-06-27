<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventFormat;
use Illuminate\Http\Request;

class EventFormatController extends Controller
{
    public function index()
    {
        $formats = EventFormat::orderBy('game')->orderBy('sort_order')->get();
        return view('admin.event-formats.index', compact('formats'));
    }

    public function create()
    {
        return view('admin.event-formats.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        EventFormat::create($data);
        return redirect()->route('admin.event-formats.index')->with('success', 'Format created.');
    }

    public function edit(EventFormat $eventFormat)
    {
        return view('admin.event-formats.edit', compact('eventFormat'));
    }

    public function update(Request $request, EventFormat $eventFormat)
    {
        $data = $this->validated($request);
        $eventFormat->update($data);
        return redirect()->route('admin.event-formats.index')->with('success', 'Format updated.');
    }

    public function destroy(EventFormat $eventFormat)
    {
        $eventFormat->delete();
        return redirect()->route('admin.event-formats.index')->with('success', 'Format deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'game'             => 'required|in:acc,lmu,iracing,ac',
            'name'             => 'required|string|max:100',
            'formation_type'   => 'nullable|string|max:60',
            'practice_mins'    => 'required|integer|min:0|max:999',
            'quali_mins'       => 'required|integer|min:0|max:999',
            'race1_mins'       => 'required|integer|min:1|max:999',
            'quali2_mins'      => 'nullable|integer|min:1|max:999',
            'race2_mins'       => 'nullable|integer|min:1|max:999',
            'pitstop_type'     => 'required|in:none,fuel_only',
            'pitstop_count'    => 'required|integer|min:0|max:10',
            'min_stop_secs'    => 'nullable|integer|min:1|max:999',
            'xcl_r_multiplier' => 'required|numeric|min:0.1|max:10',
            'server_preference'=> 'nullable|string|max:20',
            'sort_order'       => 'required|integer|min:0',
        ]);
    }
}
