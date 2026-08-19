<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Message;
use App\Models\Race;
use App\Models\RaceRegistration;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        $user   = auth()->user();
        $userId = $user->id;

        $races = Race::whereHas('registrations', fn ($q) => $q->where('user_id', $userId))
            ->where('status', 'finished')
            ->with('eventFormat')
            ->orderBy('scheduled_at', 'desc')
            ->get();

        $reportsMade = Report::where('user_id', $userId)
            ->with('race.eventFormat')
            ->orderBy('created_at', 'desc')
            ->get();

        // Reports filed before reported_user_id existed only stored a name — match those
        // too so nothing filed against this driver pre-dates the account-linked dropdown.
        $myNames = $this->myDriverNames($user);

        $reportsAgainst = Report::where('reported_user_id', $userId)
            ->when($myNames->isNotEmpty(), fn ($q) => $q->orWhere(
                fn ($q2) => $q2->whereNull('reported_user_id')->whereIn('reported_driver_name', $myNames)
            ))
            ->with(['race.eventFormat', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('reports.index', compact('races', 'reportsMade', 'reportsAgainst'));
    }

    /** GET /api/race/{race}/participants — drives the "Submitted against" dropdown. */
    public function raceParticipants(Race $race): JsonResponse
    {
        $participants = $race->registrations()
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter()
            ->reject(fn (User $u) => $u->id === auth()->id())
            ->unique('id')
            ->map(fn (User $u) => [
                'id'         => $u->id,
                'name'       => $u->displayName(),
                'avatar_url' => $u->avatarUrl(),
            ])
            ->values();

        return response()->json($participants);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'race_id'               => 'required|exists:races,id',
            'reported_user_id'      => 'required|exists:users,id',
            'session_type'          => 'required|in:R,Q,P',
            'lap_number'            => 'nullable|integer|min:1|max:999',
            'incident_corner'       => 'nullable|string|max:50',
            'description'           => 'required|string|min:20|max:2000',
            'video_url'             => 'required|url|max:500',
            'clip_good_driver_url'  => 'nullable|url|max:500',
            'clip_bad_driver_url'   => 'nullable|url|max:500',
            'clip_heli_url'         => 'nullable|url|max:500',
        ]);

        if ((int) $data['reported_user_id'] === auth()->id()) {
            return back()->withErrors(['reported_user_id' => 'You cannot report yourself.'])->withInput();
        }

        $selfParticipated = RaceRegistration::where('race_id', $data['race_id'])
            ->where('user_id', auth()->id())
            ->exists();

        if (! $selfParticipated) {
            return back()->withErrors(['race_id' => 'You did not participate in this race.'])->withInput();
        }

        $participated = RaceRegistration::where('race_id', $data['race_id'])
            ->where('user_id', $data['reported_user_id'])
            ->exists();

        if (! $participated) {
            return back()->withErrors(['reported_user_id' => 'This driver did not participate in the selected race.'])->withInput();
        }

        $reportedUser = User::findOrFail($data['reported_user_id']);

        $data['user_id']              = auth()->id();
        $data['reported_driver_name'] = $reportedUser->displayName();
        $data['reporter_driver_name'] = $this->myGamertag(auth()->user());

        $report = Report::create($data);

        Message::create([
            'user_id'      => auth()->id(),
            'title'        => 'Report submitted',
            'body'         => "Your incident report has been received and is pending review by the stewards.\n\nReported driver: {$report->reported_driver_name}\n\nYou will receive a message when a verdict has been reached.",
            'type'         => 'report_confirmation',
            'related_id'   => $report->id,
            'related_type' => Report::class,
        ]);

        return redirect()->route('reports.index')
            ->with('success', 'Your report has been submitted and is pending review.');
    }

    private function myGamertag(User $user): string
    {
        $driver = Driver::where('xuid_psid', $user->platform_id)
            ->orWhere('xuid_psid', 'T_' . strtolower($user->name))
            ->orWhere('gamertag', $user->name)
            ->first();

        return $driver->gamertag ?? $user->displayName();
    }

    /** Every name this user could plausibly have been reported under before reported_user_id existed. */
    private function myDriverNames(User $user): \Illuminate\Support\Collection
    {
        $gamertags = Driver::where('xuid_psid', $user->platform_id)
            ->orWhere('xuid_psid', 'T_' . strtolower($user->name))
            ->orWhere('gamertag', $user->name)
            ->pluck('gamertag');

        return $gamertags
            ->push($user->name, $user->displayName())
            ->filter()
            ->unique();
    }
}
