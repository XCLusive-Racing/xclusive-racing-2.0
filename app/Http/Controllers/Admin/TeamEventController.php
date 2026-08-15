<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EsportsDriver;
use App\Models\TeamEvent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TeamEventController extends Controller
{
    public function index()
    {
        $now = now();

        $upcomingEvents = TeamEvent::where('starts_at', '>=', $now)
            ->orderBy('starts_at')
            ->get();

        $pastEvents = TeamEvent::where('starts_at', '<', $now)
            ->orderBy('starts_at', 'desc')
            ->get();

        return view('admin.team-events.index', [
            'upcomingEvents'       => $upcomingEvents,
            'pastEvents'           => $pastEvents,
            'subjects'             => TeamEvent::subjects(),
            'esportsDriversByGame' => $this->esportsDriversByGame(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject'          => ['required', 'in:' . implode(',', array_keys(TeamEvent::subjects()))],
            'title'            => ['required', 'string', 'max:200'],
            'subtitle'         => ['nullable', 'string', 'max:200'],
            'starts_at'        => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'in:' . implode(',', array_keys(TeamEvent::durationOptions()))],
            'watch_url'        => ['nullable', 'url', 'max:500'],
            'image'            => ['nullable', 'image', 'max:10240'],
            'image_url'        => ['nullable', 'url', 'max:1000'],
        ]);

        $driverIds = $this->validatedDriverIds($request);

        // The datetime-local input has no offset — admins enter it in BST
        // (Europe/London), so parse it as such and convert to UTC for storage.
        $startsAt          = Carbon::createFromFormat('Y-m-d\TH:i', $data['starts_at'], 'Europe/London')->utc();
        $data['starts_at'] = $startsAt;
        $data['ends_at']   = $startsAt->copy()->addMinutes((int) $data['duration_minutes']);

        if ($request->hasFile('image')) {
            $file          = $request->file('image');
            $filename      = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $data['image'] = $file->storeAs('images/team-events', $filename, 'media');
        } elseif (!empty($data['image_url'])) {
            $data['image'] = $data['image_url'];
        }

        unset($data['image_url']);
        $teamEvent = TeamEvent::create($data);
        $teamEvent->participatingDrivers()->sync($driverIds);

        return redirect()->route('admin.team-events.index')
            ->with('success', 'Team event created.');
    }

    public function edit(TeamEvent $teamEvent)
    {
        return view('admin.team-events.edit', [
            'event'                => $teamEvent,
            'subjects'             => TeamEvent::subjects(),
            'esportsDriversByGame' => $this->esportsDriversByGame(),
            'selectedDriverIds'    => $teamEvent->participatingDrivers()->pluck('esports_drivers.id')->all(),
        ]);
    }

    public function update(Request $request, TeamEvent $teamEvent)
    {
        $data = $request->validate([
            'subject'          => ['required', 'in:' . implode(',', array_keys(TeamEvent::subjects()))],
            'title'            => ['required', 'string', 'max:200'],
            'subtitle'         => ['nullable', 'string', 'max:200'],
            'starts_at'        => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'in:' . implode(',', array_keys(TeamEvent::durationOptions()))],
            'watch_url'        => ['nullable', 'url', 'max:500'],
            'image'            => ['nullable', 'image', 'max:10240'],
            'image_url'        => ['nullable', 'url', 'max:1000'],
        ]);

        $driverIds = $this->validatedDriverIds($request);

        $startsAt          = Carbon::createFromFormat('Y-m-d\TH:i', $data['starts_at'], 'Europe/London')->utc();
        $data['starts_at'] = $startsAt;
        $data['ends_at']   = $startsAt->copy()->addMinutes((int) $data['duration_minutes']);

        if ($request->hasFile('image')) {
            if ($teamEvent->image && !str_starts_with($teamEvent->image, 'http')) {
                Storage::disk('media')->delete($teamEvent->image);
            }
            $file          = $request->file('image');
            $filename      = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $data['image'] = $file->storeAs('images/team-events', $filename, 'media');
        } elseif (!empty($data['image_url'])) {
            $data['image'] = $data['image_url'];
        } else {
            unset($data['image']); // keep existing
        }

        unset($data['image_url']);
        $teamEvent->update($data);
        $teamEvent->participatingDrivers()->sync($driverIds);

        return redirect()->route('admin.team-events.index')
            ->with('success', 'Team event updated.');
    }

    private function esportsDriversByGame()
    {
        $drivers = EsportsDriver::orderBy('sort_order')->get()->groupBy('game');

        foreach (array_values(TeamEvent::teamSubjectGames()) as $game) {
            if (! $drivers->has($game)) {
                $drivers[$game] = collect();
            }
        }

        return $drivers;
    }

    /** Decodes + validates the JSON-encoded `participating_drivers` id list from the driver picker. */
    private function validatedDriverIds(Request $request): array
    {
        $ids = json_decode((string) $request->input('participating_drivers', '[]'), true);
        if (! is_array($ids)) {
            $ids = [];
        }
        $ids = array_values(array_unique(array_map('intval', $ids)));

        return EsportsDriver::whereIn('id', $ids)->pluck('id')->all();
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
