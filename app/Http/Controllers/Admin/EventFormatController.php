<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventFormat;
use App\Models\EventTag;
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
        $tags = EventTag::orderBy('name')->get();

        return view('admin.event-formats.create', compact('tags'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        EventFormat::create($data);

        return redirect()->route('admin.event-formats.index')->with('success', 'Format created.');
    }

    public function edit(EventFormat $eventFormat)
    {
        $tags = EventTag::orderBy('name')->get();

        return view('admin.event-formats.edit', compact('eventFormat', 'tags'));
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
        $data = $request->validate([
            'game' => 'required|in:acc,lmu,iracing,ac',
            'name' => 'required|string|max:100',
            'formation_type' => 'nullable|string|max:60',
            'practice_mins' => 'required|integer|min:0|max:999',
            'quali_mins' => 'required|integer|min:0|max:999',
            'race1_mins' => 'required|integer|min:1|max:999',
            'quali2_mins' => 'nullable|integer|min:1|max:999',
            'race2_mins' => 'nullable|integer|min:1|max:999',
            'pitstop_count' => 'required|integer|min:0|max:10',
            'mandatory_refuelling' => 'nullable|boolean',
            'mandatory_tyre_change' => 'nullable|boolean',
            'fixed_stop_time' => 'nullable|boolean',
            'xcl_r_multiplier' => 'required|numeric|min:0.1|max:10',
            'server_preference' => 'nullable|string|max:20',
            'server_group' => 'nullable|in:short,medium,long',
            'default_event_tag' => 'nullable|exists:event_tags,slug',
            'sort_order' => 'required|integer|min:0',
        ]);

        // No separate pitstop type to pick: a stop count means a stop, and the options
        // below say what that stop has to include.
        $hasStop = (int) $data['pitstop_count'] > 0;
        $data['pitstop_type'] = $hasStop ? 'fuel_only' : 'none';
        $data['mandatory_refuelling'] = $hasStop && $request->boolean('mandatory_refuelling');
        $data['mandatory_tyre_change'] = $hasStop && $request->boolean('mandatory_tyre_change');
        $data['min_stop_secs'] = $hasStop && $request->boolean('fixed_stop_time') ? EventFormat::FIXED_STOP_SECS : null;
        unset($data['fixed_stop_time']);

        return $data;
    }
}
