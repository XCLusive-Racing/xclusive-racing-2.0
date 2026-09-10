<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\EventTag;
use App\Models\Message;
use App\Models\Race;
use App\Models\RaceClass;
use App\Models\RaceRegistration;
use App\Models\RaceTeamEntry;
use App\Models\User;
use App\Services\Contracts\ServerConfigGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RaceController extends Controller
{
    public function index()
    {
        $races = Race::select(['id','title','game','track','scheduled_at','status','is_championship','event_tag','max_drivers','duration_key','image','icon','description','sr_requirement','min_rating','max_rating','car_class','weather','event_format_id','is_endurance'])
            ->with('eventFormat:id,race1_mins,race2_mins')
            ->where('status', '!=', 'finished')
            ->orderBy('scheduled_at')
            ->get();
        $races->loadCount(['registrations', 'teamEntries']);

        // "Rookies" and "Test Event" are real EventTag rows (so any race still
        // tagged with one keeps working) but aren't genuine event-type filters
        // for the public browse page — excluded here rather than deleted.
        $eventTags = EventTag::whereNotIn('slug', ['rookies', 'test-event'])->orderBy('name')->get();

        return view('race.index', compact('races', 'eventTags'));
    }

    public function show(Race $race)
    {
        $race->load([
            'raceClasses', 'registrations.user', 'registrations.raceClass', 'registrations.teamEntry.team',
            'raceResults.user', 'eventFormat', 'teamEntries',
            // Same tenant-scope bypass as ftpServer above — a guest viewing this page
            // must still see the practice server's name regardless of league.
            'practiceServerSession.practiceServer.ftpServer' => fn ($q) => $q->withoutTenantScope(),
        ]);
        $isRegistered   = false;
        $myRegistration = null;
        $userTeam        = null;
        $myTeamEntries   = collect();
        $myRegisteredAt  = null;

        if (auth()->check()) {
            $myRegistration = $race->registrations->firstWhere('user_id', auth()->id());
            $isRegistered   = $myRegistration !== null;
            $myRegisteredAt = $myRegistration?->created_at;
            $userTeam       = auth()->user()->ownedRacingTeams()->with('members')->first();
            if ($userTeam) {
                $myTeamEntries = RaceTeamEntry::where('race_id', $race->id)
                    ->where('racing_team_id', $userTeam->id)
                    ->with(['registrations.user', 'startingDriver'])
                    ->get();

                $earliestTeamEntry = $myTeamEntries->min('created_at');
                if ($earliestTeamEntry && (!$myRegisteredAt || $earliestTeamEntry < $myRegisteredAt)) {
                    $myRegisteredAt = $earliestTeamEntry;
                    $isRegistered   = true;
                }
            }
        }

        $platformIds = $race->registrations->pluck('user.platform_id')->filter()->values()->all();
        $driverMap   = Driver::whereIn('xuid_psid', $platformIds)
            ->get(['id', 'xuid_psid'])
            ->keyBy('xuid_psid');

        return view('race.show', compact('race', 'isRegistered', 'myRegistration', 'myRegisteredAt', 'driverMap', 'userTeam', 'myTeamEntries'));
    }

    // Serves a single-event .ics — the universal format every calendar app opens. Linked
    // to as a webcal:// URL for "Apple Calendar" (see race/partials/add-to-calendar.blade.php),
    // which hands off straight to the OS calendar app instead of downloading a file;
    // "inline" here backs that up for browsers that fetch it as a plain https:// URL
    // instead of honoring the webcal: scheme. Google/Outlook get their own one-click
    // deep-links built from Race::googleCalendarUrl()/outlookCalendarUrl() instead.
    public function calendar(Race $race)
    {
        [$start, $end] = $race->calendarWindow();

        $escape = fn (string $v) => addcslashes($v, ",;\\") ;
        $fold   = fn (string $line) => wordwrap($line, 73, "\r\n ", true);

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//XCLusive Racing//Events//EN',
            'CALSCALE:GREGORIAN',
            'BEGIN:VEVENT',
            'UID:race-' . $race->id . '@xclusiveracing.com',
            'DTSTAMP:' . now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:' . $start->format('Ymd\THis\Z'),
            'DTEND:' . $end->format('Ymd\THis\Z'),
            $fold('SUMMARY:' . $escape($race->title . ' — ' . $race->track)),
            $fold('LOCATION:' . $escape($race->track . ' (' . $race->gameLabel() . ')')),
            $fold('DESCRIPTION:' . $escape(route('events.show', $race))),
            $fold('URL:' . route('events.show', $race)),
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        $filename = \Illuminate\Support\Str::slug($race->title . '-' . $race->track) . '.ics';

        return response(implode("\r\n", $lines) . "\r\n", 200, [
            'Content-Type'        => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    public function register(Request $request, Race $race)
    {
        if (auth()->user()->isSuspended()) {
            return back()->with('error', 'Your account has been suspended. Please contact an administrator.');
        }

        if (! $race->registrationOpen()) {
            return back()->with('error', 'Registration is closed for this race.');
        }

        if ($race->isRegistered(auth()->user())) {
            return back()->with('error', 'You are already registered for this race.');
        }

        if ($failure = auth()->user()->requirementFailure($race->game, $race->sr_requirement, $race->min_rating, $race->max_rating)) {
            return back()->with('error', $failure);
        }

        $raceClassId = null;

        if ($race->is_multiclass && $race->raceClasses->isNotEmpty()) {
            $validated = $request->validate([
                'race_class_id' => ['required', 'integer'],
            ]);

            $raceClass = RaceClass::where('id', $validated['race_class_id'])
                ->where('race_id', $race->id)
                ->firstOrFail();

            if ($raceClass->isFull()) {
                return back()->with('error', 'The selected class is full.');
            }

            if ($failure = auth()->user()->requirementFailure($race->game, $raceClass->sr_requirement, $raceClass->min_rating, $raceClass->max_rating ?? null)) {
                return back()->with('error', $failure);
            }

            $raceClassId = $raceClass->id;
        } else {
            if ($race->isFull()) {
                return back()->with('error', 'This race is full.');
            }
        }

        // The registering driver must see the server's connection details regardless
        // of their own league membership (most of the time this is XCL's own server,
        // which is a real, tenant-scoped League row since Phase 2.5) — bypass
        // explicitly rather than relying on the scope to let it through.
        $race->load(['ftpServer' => fn ($q) => $q->withoutTenantScope()]);

        try {
            DB::transaction(function () use ($race, $raceClassId) {
                $existing = RaceRegistration::withTrashed()
                    ->where('race_id', $race->id)
                    ->where('user_id', auth()->id())
                    ->first();

                if ($existing) {
                    $existing->restore();
                    $existing->update(['race_class_id' => $raceClassId]);
                } else {
                    RaceRegistration::create([
                        'race_id'       => $race->id,
                        'user_id'       => auth()->id(),
                        'race_class_id' => $raceClassId,
                    ]);
                }

                $config     = app(ServerConfigGenerator::class)->settings($race, $race->ftpServer);
                $serverName = $config['serverName'] ?? 'To be announced';
                $password   = $config['password']   ?? 'To be announced';

                Message::create([
                    'user_id'      => auth()->id(),
                    'title'        => 'Registered: ' . $race->title,
                    'body'         => "You have successfully registered for {$race->title}.\n\nServer: {$serverName}\nPassword: {$password}\n\nSee you on track!",
                    'type'         => 'event_registration',
                    'related_id'   => $race->id,
                    'related_type' => Race::class,
                ]);
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Something went wrong while processing your registration. Please try again.');
        }

        return back()->with('success', 'You have been registered for ' . $race->title . '! Server details are in your inbox.');
    }

    public function unregister(Race $race)
    {
        if ($race->status !== 'open') {
            return back()->with('error', 'You cannot unregister from a closed race.');
        }

        RaceRegistration::where('race_id', $race->id)
            ->where('user_id', auth()->id())
            ->delete();

        return back()->with('success', 'You have been unregistered from ' . $race->title . '.');
    }

    public function registerTeam(Request $request, Race $race)
    {
        if (auth()->user()->isSuspended()) {
            return back()->with('error', 'Your account has been suspended. Please contact an administrator.');
        }

        if (!$race->registrationOpen()) {
            return back()->with('error', 'Registration is closed for this race.');
        }

        $team = auth()->user()->ownedRacingTeams()->with('members')->first();
        if (!$team) {
            return back()->with('error', 'You do not own a racing team.');
        }

        $validated = $request->validate([
            'car_number'        => ['required', 'integer', 'min:0', 'max:999'],
            'car_model'         => ['nullable', 'string', 'max:60'],
            'driver_ids'        => ['required', 'array', 'min:1'],
            'driver_ids.*'      => ['integer'],
            'starting_driver_id' => ['required', 'integer'],
        ]);

        $eligibleIds = $team->members->pluck('id')->push($team->owner_id)->unique();
        $selectedIds = collect($validated['driver_ids'])
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $eligibleIds->contains($id))
            ->values();

        if ($selectedIds->isEmpty()) {
            return back()->with('error', 'Please select at least one driver from your team.');
        }

        $startingDriverId = (int) $validated['starting_driver_id'];
        if (!$selectedIds->contains($startingDriverId)) {
            return back()->with('error', 'The starting driver must be one of the selected drivers.');
        }

        $users = User::whereIn('id', $selectedIds)->get()->keyBy('id');

        foreach ($users as $user) {
            if ($failure = $user->requirementFailure($race->game, $race->sr_requirement, $race->min_rating, $race->max_rating)) {
                return back()->with('error', $user->displayName() . ': ' . $failure);
            }
            if ($race->isRegistered($user)) {
                return back()->with('error', $user->displayName() . ' is already registered for this race.');
            }
        }

        $carNumberTaken = RaceTeamEntry::where('race_id', $race->id)
            ->where('car_number', $validated['car_number'])
            ->exists();

        if ($carNumberTaken) {
            return back()->with('error', 'Car number #' . $validated['car_number'] . ' is already taken for this race.');
        }

        if ($race->max_drivers !== null) {
            $currentTeams = $race->teamEntries()->count();
            if ($currentTeams + 1 > $race->max_drivers) {
                return back()->with('error', 'This race is full. No more team slots available.');
            }
        }

        // The registering driver must see the server's connection details regardless
        // of their own league membership (most of the time this is XCL's own server,
        // which is a real, tenant-scoped League row since Phase 2.5) — bypass
        // explicitly rather than relying on the scope to let it through.
        $race->load(['ftpServer' => fn ($q) => $q->withoutTenantScope()]);

        try {
            DB::transaction(function () use ($race, $team, $selectedIds, $users, $validated, $startingDriverId) {
                $entry = RaceTeamEntry::create([
                    'race_id'            => $race->id,
                    'racing_team_id'     => $team->id,
                    'car_number'         => $validated['car_number'],
                    'car_model'          => $validated['car_model'] ?? null,
                    'starting_driver_id' => $startingDriverId,
                ]);

                foreach ($selectedIds as $userId) {
                    $existingReg = RaceRegistration::withTrashed()
                        ->where('race_id', $race->id)
                        ->where('user_id', $userId)
                        ->first();

                    if ($existingReg) {
                        $existingReg->restore();
                        $existingReg->update(['team_entry_id' => $entry->id, 'race_class_id' => null]);
                    } else {
                        RaceRegistration::create([
                            'race_id'       => $race->id,
                            'user_id'       => $userId,
                            'team_entry_id' => $entry->id,
                        ]);
                    }
                }

                $config     = app(ServerConfigGenerator::class)->settings($race, $race->ftpServer);
                $serverName = $config['serverName'] ?? 'To be announced';
                $password   = $config['password']   ?? 'To be announced';

                foreach ($users as $user) {
                    Message::create([
                        'user_id'      => $user->id,
                        'title'        => 'Team Registered: ' . $race->title,
                        'body'         => "Your team {$team->name} has been registered for {$race->title}.\n\nServer: {$serverName}\nPassword: {$password}\n\nSee you on track!",
                        'type'         => 'event_registration',
                        'related_id'   => $race->id,
                        'related_type' => Race::class,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Something went wrong while processing your registration. Please try again.');
        }

        return back()->with('success', $team->name . ' has been registered for ' . $race->title . '!');
    }

    public function unregisterTeam(Race $race, RaceTeamEntry $entry)
    {
        if ($race->status !== 'open') {
            return back()->with('error', 'You cannot unregister from a closed race.');
        }

        $team = auth()->user()->ownedRacingTeams()->first();
        if (!$team) {
            return back()->with('error', 'You do not own a racing team.');
        }

        if ($entry->race_id !== $race->id || $entry->racing_team_id !== $team->id) {
            return back()->with('error', 'This entry does not belong to your team.');
        }

        DB::transaction(function () use ($entry) {
            $entry->registrations()->delete();
            $entry->delete();
        });

        return back()->with('success', 'Car #' . $entry->car_number . ' has been unregistered from ' . $race->title . '.');
    }
}