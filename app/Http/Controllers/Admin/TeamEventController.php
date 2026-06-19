<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TeamEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TeamEventController extends Controller
{
    public function index()
    {
        $events = TeamEvent::orderBy('starts_at')->get();

        return view('admin.team-events.index', [
            'events'   => $events,
            'subjects' => TeamEvent::subjects(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject'   => ['required', 'in:' . implode(',', array_keys(TeamEvent::subjects()))],
            'title'     => ['required', 'string', 'max:200'],
            'subtitle'  => ['nullable', 'string', 'max:200'],
            'starts_at' => ['required', 'date'],
            'watch_url' => ['nullable', 'url', 'max:500'],
            'image'     => ['nullable', 'image', 'max:10240'],
        ]);

        if ($request->hasFile('image')) {
            $file     = $request->file('image');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $data['image'] = $file->storeAs('images/team-events', $filename, 'media');
        }

        TeamEvent::create($data);

        return redirect()->route('admin.team-events.index')
            ->with('success', 'Team event created.');
    }

    public function destroy(TeamEvent $teamEvent)
    {
        if ($teamEvent->image) {
            Storage::disk('media')->delete($teamEvent->image);
        }
        $teamEvent->delete();
        return back()->with('success', 'Event deleted.');
    }
}
