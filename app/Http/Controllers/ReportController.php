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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $userId = $user->id;

        // Rounds of a championship that doesn't use XCL stewarding can't be reported.
        // Tenant scope bypassed: a driver isn't a member of the championship's league.
        $races = Race::whereHas('registrations', fn ($q) => $q->where('user_id', $userId))
            ->where('status', 'finished')
            ->with(['eventFormat', 'championship' => fn ($q) => $q->withoutTenantScope()])
            ->orderBy('scheduled_at', 'desc')
            ->get()
            ->reject(fn (Race $race) => $race->championship && ! $race->championship->usesXclStewarding())
            ->values();

        $reportsMade = Report::where('user_id', $userId)
            ->with('race.eventFormat')
            ->orderBy('created_at', 'desc')
            ->get();

        // Reports filed before reported_user_id existed only stored a name — match those
        // too so nothing filed against this driver pre-dates the account-linked dropdown.
        $myNames = $this->myDriverNames($user);

        // A retracted report was withdrawn by its reporter, so the reported driver never sees it.
        $reportsAgainst = Report::where(function ($q) use ($userId, $myNames) {
            $q->where('reported_user_id', $userId)
                ->when($myNames->isNotEmpty(), fn ($q) => $q->orWhere(
                    fn ($q2) => $q2->whereNull('reported_user_id')->whereIn('reported_driver_name', $myNames)
                ));
        })
            ->where('status', '!=', 'retracted')
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
                'id' => $u->id,
                'name' => $u->displayName(),
                'avatar_url' => $u->avatarUrl(),
            ])
            ->values();

        return response()->json($participants);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'race_id' => 'required|exists:races,id',
            'reported_user_id' => 'required|exists:users,id',
            'session_type' => 'required|in:R,Q,P',
            'lap_number' => 'nullable|integer|min:1|max:999',
            'incident_corner' => 'nullable|string|max:50',
            'description' => 'required|string|min:20|max:2000',
            'video_url' => 'required|url|max:500',
            'clip_good_driver_url' => 'nullable|url|max:500',
            'clip_bad_driver_url' => 'nullable|url|max:500',
            'clip_heli_url' => 'nullable|url|max:500',
            'hide_reporter_name' => 'nullable|boolean',
        ]);

        $data['hide_reporter_name'] = $request->boolean('hide_reporter_name');

        if ((int) $data['reported_user_id'] === auth()->id()) {
            return back()->withErrors(['reported_user_id' => 'You cannot report yourself.'])->withInput();
        }

        $championship = Race::find($data['race_id'])->championship()->withoutTenantScope()->first();
        if ($championship && ! $championship->usesXclStewarding()) {
            return back()->withErrors(['race_id' => 'This championship doesn\'t use XCL stewarding — contact the league about incidents.'])->withInput();
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

        $data['user_id'] = auth()->id();
        $data['reported_driver_name'] = $reportedUser->displayName();
        $data['reporter_driver_name'] = $this->myGamertag(auth()->user());

        $report = Report::create($data);

        Message::create([
            'user_id' => auth()->id(),
            'title' => 'Report submitted',
            'body' => "Your incident report has been received and is pending review by the stewards.\n\nReported driver: {$report->reported_driver_name}\n\nYou will receive a message when a verdict has been reached.",
            'type' => 'report_confirmation',
            'related_id' => $report->id,
            'related_type' => Report::class,
        ]);

        return redirect()->route('reports.index')
            ->with('success', 'Your report has been submitted and is pending review.');
    }

    public function edit(Report $report)
    {
        abort_unless($report->user_id === auth()->id(), 403);

        if (! $report->isChangeableByReporter()) {
            return redirect()->route('reports.index')->with('error', $this->lockedMessage());
        }

        return view('reports.edit', compact('report'));
    }

    public function update(Request $request, Report $report)
    {
        abort_unless($report->user_id === auth()->id(), 403);

        // Race and reported driver stay as filed. To change those, retract and file a new report.
        $data = $request->validate([
            'session_type' => 'required|in:R,Q,P',
            'lap_number' => 'nullable|integer|min:1|max:999',
            'incident_corner' => 'nullable|string|max:50',
            'description' => 'required|string|min:20|max:2000',
            'video_url' => 'required|url|max:500',
            'clip_bad_driver_url' => 'nullable|url|max:500',
            'clip_heli_url' => 'nullable|url|max:500',
        ]);
        $data['hide_reporter_name'] = $request->boolean('hide_reporter_name');

        $saved = DB::transaction(function () use ($report, $data) {
            // Re-check under a row lock: a steward may have picked it up since the form loaded.
            $fresh = Report::lockForUpdate()->find($report->id);
            if (! $fresh->isChangeableByReporter()) {
                return false;
            }
            $fresh->update($data);

            return true;
        });

        if (! $saved) {
            return redirect()->route('reports.index')->with('error', $this->lockedMessage());
        }

        return redirect()->route('reports.index')->with('success', 'Your report has been updated.');
    }

    public function retract(Report $report)
    {
        abort_unless($report->user_id === auth()->id(), 403);

        $retracted = DB::transaction(function () use ($report) {
            $fresh = Report::lockForUpdate()->find($report->id);
            if (! $fresh->isChangeableByReporter()) {
                return false;
            }
            $fresh->update(['status' => 'retracted']);

            return true;
        });

        if (! $retracted) {
            return redirect()->route('reports.index')->with('error', $this->lockedMessage());
        }

        return redirect()->route('reports.index')->with('success', 'Your report has been retracted.');
    }

    private function lockedMessage(): string
    {
        return 'This report is already under investigation (or closed), so it can no longer be edited or retracted.';
    }

    private function myGamertag(User $user): string
    {
        $driver = Driver::where('xuid_psid', $user->platform_id)
            ->orWhere('xuid_psid', 'T_'.strtolower($user->name))
            ->orWhere('gamertag', $user->name)
            ->first();

        return $driver->gamertag ?? $user->displayName();
    }

    /** Every name this user could plausibly have been reported under before reported_user_id existed. */
    private function myDriverNames(User $user): Collection
    {
        $gamertags = Driver::where('xuid_psid', $user->platform_id)
            ->orWhere('xuid_psid', 'T_'.strtolower($user->name))
            ->orWhere('gamertag', $user->name)
            ->pluck('gamertag');

        return $gamertags
            ->push($user->name, $user->displayName())
            ->filter()
            ->unique();
    }
}
