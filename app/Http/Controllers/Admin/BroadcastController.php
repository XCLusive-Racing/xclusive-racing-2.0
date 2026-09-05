<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Broadcast;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BroadcastController extends Controller
{
    public function index()
    {
        $now = now();

        $upcomingBroadcasts = Broadcast::where('ends_at', '>', $now)
            ->orderBy('starts_at')
            ->get();

        $pastBroadcasts = Broadcast::where('ends_at', '<=', $now)
            ->orderBy('starts_at', 'desc')
            ->get();

        return view('admin.broadcasts.index', compact('upcomingBroadcasts', 'pastBroadcasts'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $data['author_id'] = auth()->id();
        Broadcast::create($data);

        return redirect()->route('admin.broadcasts.index')
            ->with('success', 'Broadcast scheduled.');
    }

    public function edit(Broadcast $broadcast)
    {
        return view('admin.broadcasts.edit', compact('broadcast'));
    }

    public function update(Request $request, Broadcast $broadcast)
    {
        $data = $this->validated($request);

        $broadcast->update($data);

        return redirect()->route('admin.broadcasts.index')
            ->with('success', 'Broadcast updated.');
    }

    public function destroy(Broadcast $broadcast)
    {
        $broadcast->delete();

        return back()->with('success', 'Broadcast deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title'            => ['required', 'string', 'max:200'],
            'subtitle'         => ['nullable', 'string', 'max:200'],
            'series'           => ['nullable', 'string', 'max:30'],
            'color'            => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'watch_url'        => ['required', 'url', 'max:500'],
            'starts_at'        => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'in:' . implode(',', array_keys(Broadcast::durationOptions()))],
        ]);

        // The datetime-local input has no offset — admins enter it in BST
        // (Europe/London), so parse it as such and convert to UTC for storage.
        $startsAt           = Carbon::createFromFormat('Y-m-d\TH:i', $data['starts_at'], 'Europe/London')->utc();
        $data['starts_at']  = $startsAt;
        $data['ends_at']    = $startsAt->copy()->addMinutes((int) $data['duration_minutes']);
        $data['color']      = $data['color'] ?: '#cc0000';

        return $data;
    }
}
