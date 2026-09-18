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
use Illuminate\Support\Str;

class RaceController extends Controller
{
    public function index()
    {
        $races = Race::select(['id', 'title', 'game', 'track', 'scheduled_at', 'status', 'is_championship', 'event_tag', 'max_drivers', 'duration_key', 'image', 'icon', 'description', 'sr_requirement', 'min_rating', 'max_rating', 'car_class', 'weather', 'event_format_id', 'is_endurance', 'championship_id'])
            ->with('eventFormat:id,race1_mins,race2_mins')
            ->where('status', '!=', 'finished')
            // A championship round is only a public event once its own championship
            // is (Championship::PUBLIC_STATUSES + not hidden) -- a draft
            // championship's rounds, or a published-but-hidden one's, shouldn't leak
            // onto the public Events page just because the Race row itself is open.
            // withoutTenantScope() here because Championship carries TenantScope
            // (league-owned data) and this page has no league context to filter by
            // -- an anonymous visitor has none, which would otherwise hide every
            // championship round from every public visitor (TenantScope::apply()
            // resolves an empty league list to "matches nothing").
            ->where(function ($q) {
                $q->whereNull('championship_id')
                    ->orWhereHas('championship', fn ($cq) => $cq->withoutTenantScope()->publiclyVisible());
            })
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
        $isRegistered = false;
        $myRegistration = null;
        $userTeam = null;
        $myTeamEntries = collect();
        $myRegisteredAt = null;

        // A driver-swap championship round has no is_endurance flag of its own (that column
        // is Custom-Race-only, see docs/championships/PLAN.md's Phase 4 scope decision) — a
        // team is entered here automatically from the championship-level registration
        // (ChampionshipTeamEntryService), so this round still needs the same TEAM ENTRY card
        // (and its per-race unregister) that Custom Race endurance events already get.
        $isTeamRace = (bool) $race->is_endurance;
        $isChampionshipTeamRound = false;

        if (! $isTeamRace && $race->championship_id) {
            $championship = $race->championship()->withoutTenantScope()->first();
            if ($championship && ($championship->settings->format->driver_swaps_enabled ?? false)) {
                $isTeamRace = true;
                $isChampionshipTeamRound = true;
            }
        }

        if (auth()->check()) {
            $myRegistration = $race->registrations->firstWhere('user_id', auth()->id());
            $isRegistered = $myRegistration !== null;
            $myRegisteredAt = $myRegistration?->created_at;
            $userTeam = auth()->user()->manageableRacingTeam();
            if ($userTeam) {
                $myTeamEntries = RaceTeamEntry::where('race_id', $race->id)
                    ->where('racing_team_id', $userTeam->id)
                    ->with(['registrations.user', 'startingDriver'])
                    ->get();

                $earliestTeamEntry = $myTeamEntries->min('created_at');
                if ($earliestTeamEntry && (! $myRegisteredAt || $earliestTeamEntry < $myRegisteredAt)) {
                    $myRegisteredAt = $earliestTeamEntry;
                    $isRegistered = true;
                }
            }
        }

        $platformIds = $race->registrations->pluck('user.platform_id')->filter()->values()->all();
        $driverMap = Driver::whereIn('xuid_psid', $platformIds)
            ->get(['id', 'xuid_psid'])
            ->keyBy('xuid_psid');

        return view('race.show', compact(
            'race', 'isRegistered', 'myRegistration', 'myRegisteredAt', 'driverMap', 'userTeam', 'myTeamEntries',
            'isTeamRace', 'isChampionshipTeamRound'
        ));
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

        $escape = fn (string $v) => addcslashes($v, ',;\\');
        $fold = fn (string $line) => wordwrap($line, 73, "\r\n ", true);

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//XCLusive Racing//Events//EN',
            'CALSCALE:GREGORIAN',
            'BEGIN:VEVENT',
            'UID:race-'.$race->id.'@xclusiveracing.com',
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$start->format('Ymd\THis\Z'),
            'DTEND:'.$end->format('Ymd\THis\Z'),
            $fold('SUMMARY:'.$escape($race->title.' — '.$race->track)),
            $fold('LOCATION:'.$escape($race->track.' ('.$race->gameLabel().')')),
            $fold('DESCRIPTION:'.$escape(route('events.show', $race))),
            $fold('URL:'.route('events.show', $race)),
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        $filename = Str::slug($race->title.'-'.$race->track).'.ics';

        return response(implode("\r\n", $lines)."\r\n", 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
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

            if ($failure = auth()->user()->requirementFailure($race->game, $raceClass->sr_requirement, $raceClass->min_rating, $raceClass->max_rating ?? null)) {
                return back()->with('error', $failure);
            }

            $raceClassId = $raceClass->id;
        }

        // The registering driver must see the server's connection details regardless
        // of their own league membership (most of the time this is XCL's own server,
        // which is a real, tenant-scoped League row since Phase 2.5) — bypass
        // explicitly rather than relying on the scope to let it through.
        $race->load(['ftpServer' => fn ($q) => $q->withoutTenantScope()]);

        $waitlisted = false;

        try {
            DB::transaction(function () use ($race, $raceClassId, &$waitlisted) {
                // A full race no longer rejects the signup -- it joins the waiting list
                // instead (see Race::isRegistrationWaitlisted()). The lock is still
                // needed so two people registering for the same last spot at the same
                // instant get a stable, unambiguous FIFO order rather than a race on
                // whose insert's created_at ends up earlier.
                Race::where('id', $race->id)->lockForUpdate()->first();
                if ($raceClassId !== null) {
                    RaceClass::where('id', $raceClassId)->lockForUpdate()->first();
                }

                $existing = RaceRegistration::withTrashed()
                    ->where('race_id', $race->id)
                    ->where('user_id', auth()->id())
                    ->first();

                if ($existing) {
                    $existing->restore();
                    $existing->race_class_id = $raceClassId;
                    // Re-registering joins the back of the queue at the current moment,
                    // not wherever their original (now stale) signup time would rank --
                    // otherwise cancelling and re-registering would unfairly cut the line.
                    $existing->created_at = now();
                    $existing->save();
                    $registration = $existing;
                } else {
                    $registration = RaceRegistration::create([
                        'race_id' => $race->id,
                        'user_id' => auth()->id(),
                        'race_class_id' => $raceClassId,
                    ]);
                }

                $waitlisted = $race->isRegistrationWaitlisted($registration);

                $config = app(ServerConfigGenerator::class)->settings($race, $race->ftpServer);
                $serverName = $config['serverName'] ?? 'To be announced';
                $password = $config['password'] ?? 'To be announced';

                if ($waitlisted) {
                    $position = $race->waitlistPosition($registration);

                    Message::create([
                        'user_id' => auth()->id(),
                        'title' => 'Waiting list: '.$race->title,
                        'body' => "{$race->title} is full, so you've been added to the waiting list at position {$position}. You'll be moved onto the entry list automatically (and notified here) if a spot opens up.",
                        'type' => 'event_registration',
                        'related_id' => $race->id,
                        'related_type' => Race::class,
                    ]);
                } else {
                    Message::create([
                        'user_id' => auth()->id(),
                        'title' => 'Registered: '.$race->title,
                        'body' => "You have successfully registered for {$race->title}.\n\nServer: {$serverName}\nPassword: {$password}\n\nSee you on track!",
                        'type' => 'event_registration',
                        'related_id' => $race->id,
                        'related_type' => Race::class,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Something went wrong while processing your registration. Please try again.');
        }

        if ($waitlisted) {
            return back()->with('success', $race->title.' is full — you have been added to the waiting list. You will be registered automatically if a spot opens up.');
        }

        return back()->with('success', 'You have been registered for '.$race->title.'! Server details are in your inbox.');
    }

    public function unregister(Race $race)
    {
        if ($race->status !== 'open') {
            return back()->with('error', 'You cannot unregister from a closed race.');
        }

        $registration = RaceRegistration::where('race_id', $race->id)
            ->where('user_id', auth()->id())
            ->first();

        if (! $registration) {
            return back()->with('error', 'You are not registered for this race.');
        }

        $raceClassId = $registration->race_class_id;
        $registration->delete();

        $this->promoteNextWaitlisted($race, $raceClassId);

        return back()->with('success', 'You have been unregistered from '.$race->title.'.');
    }

    // A cancelled/soft-deleted registration can free up to two independent slots at
    // once: a class-level one (if that class has its own cap) and a race-wide one
    // (the combined cap across every class, when set) -- see
    // Race::isRegistrationWaitlisted() for why both apply. Since "waitlisted" is a
    // live rank rather than a stored flag, nothing needs to be moved: this only needs
    // to find and notify whichever driver(s) just crossed into the cap, the same way a
    // fresh registration is confirmed, so they actually know to show up.
    private function promoteNextWaitlisted(Race $race, ?int $raceClassId): void
    {
        $notifiedUserIds = [];

        if ($raceClassId !== null) {
            $raceClass = RaceClass::find($raceClassId);
            if ($raceClass && $raceClass->max_drivers !== null) {
                // The registration now sitting in the last active slot (0-indexed
                // position cap - 1) is the one that just moved from waitlisted to active.
                $promoted = $raceClass->registrations()->orderBy('created_at')->orderBy('id')
                    ->skip($raceClass->max_drivers - 1)->first();

                if ($promoted) {
                    $this->notifyPromotedDriver($race, $promoted);
                    $notifiedUserIds[] = $promoted->user_id;
                }
            }
        }

        if ($race->max_drivers !== null) {
            $promoted = $race->registrations()->orderBy('created_at')->orderBy('id')
                ->skip($race->max_drivers - 1)->first();

            if ($promoted && ! in_array($promoted->user_id, $notifiedUserIds, true)) {
                $this->notifyPromotedDriver($race, $promoted);
            }
        }
    }

    private function notifyPromotedDriver(Race $race, RaceRegistration $promoted): void
    {
        $race->loadMissing(['ftpServer' => fn ($q) => $q->withoutTenantScope()]);
        $config = app(ServerConfigGenerator::class)->settings($race, $race->ftpServer);
        $serverName = $config['serverName'] ?? 'To be announced';
        $password = $config['password'] ?? 'To be announced';

        Message::create([
            'user_id' => $promoted->user_id,
            'title' => "You're in: ".$race->title,
            'body' => "A spot opened up in {$race->title} and you've been moved from the waiting list onto the entry list.\n\nServer: {$serverName}\nPassword: {$password}\n\nSee you on track!",
            'type' => 'event_registration',
            'related_id' => $race->id,
            'related_type' => Race::class,
        ]);
    }

    public function registerTeam(Request $request, Race $race)
    {
        if (auth()->user()->isSuspended()) {
            return back()->with('error', 'Your account has been suspended. Please contact an administrator.');
        }

        if (! $race->registrationOpen()) {
            return back()->with('error', 'Registration is closed for this race.');
        }

        $team = auth()->user()->manageableRacingTeam();
        if (! $team) {
            return back()->with('error', 'You do not own or manage a racing team.');
        }

        $validated = $request->validate([
            'car_number' => ['required', 'integer', 'min:0', 'max:999'],
            'car_model' => ['nullable', 'string', 'max:60'],
            'driver_ids' => ['required', 'array', 'min:1'],
            'driver_ids.*' => ['integer'],
            'starting_driver_id' => ['required', 'integer'],
        ]);

        $eligibleIds = $team->members->pluck('id')->push($team->owner_id)->unique();
        $selectedIds = collect($validated['driver_ids'])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $eligibleIds->contains($id))
            ->values();

        if ($selectedIds->isEmpty()) {
            return back()->with('error', 'Please select at least one driver from your team.');
        }

        $startingDriverId = (int) $validated['starting_driver_id'];
        if (! $selectedIds->contains($startingDriverId)) {
            return back()->with('error', 'The starting driver must be one of the selected drivers.');
        }

        $users = User::whereIn('id', $selectedIds)->get()->keyBy('id');

        foreach ($users as $user) {
            if ($failure = $user->requirementFailure($race->game, $race->sr_requirement, $race->min_rating, $race->max_rating)) {
                return back()->with('error', $user->displayName().': '.$failure);
            }
            if ($race->isRegistered($user)) {
                return back()->with('error', $user->displayName().' is already registered for this race.');
            }
        }

        // The registering driver must see the server's connection details regardless
        // of their own league membership (most of the time this is XCL's own server,
        // which is a real, tenant-scoped League row since Phase 2.5) — bypass
        // explicitly rather than relying on the scope to let it through.
        $race->load(['ftpServer' => fn ($q) => $q->withoutTenantScope()]);

        $blockError = null;

        try {
            DB::transaction(function () use ($race, $team, $selectedIds, $users, $validated, $startingDriverId, &$blockError) {
                // Re-check car number and capacity against a locked row -- the plain
                // exists()/count() checks above ran outside any transaction, so two
                // teams registering for the last slot (or the same car number) at
                // the same moment could both pass before either insert commits.
                // Locking here serializes concurrent team registrations for this
                // race, same fix as the solo register() path above.
                Race::where('id', $race->id)->lockForUpdate()->first();

                $carNumberTaken = RaceTeamEntry::where('race_id', $race->id)
                    ->where('car_number', $validated['car_number'])
                    ->exists();
                if ($carNumberTaken) {
                    $blockError = 'Car number #'.$validated['car_number'].' is already taken for this race.';

                    return;
                }

                if ($race->max_drivers !== null) {
                    $currentTeams = $race->teamEntries()->count();
                    if ($currentTeams + 1 > $race->max_drivers) {
                        $blockError = 'This race is full. No more team slots available.';

                        return;
                    }
                }

                $entry = RaceTeamEntry::create([
                    'race_id' => $race->id,
                    'racing_team_id' => $team->id,
                    'car_number' => $validated['car_number'],
                    'car_model' => $validated['car_model'] ?? null,
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
                            'race_id' => $race->id,
                            'user_id' => $userId,
                            'team_entry_id' => $entry->id,
                        ]);
                    }
                }

                $config = app(ServerConfigGenerator::class)->settings($race, $race->ftpServer);
                $serverName = $config['serverName'] ?? 'To be announced';
                $password = $config['password'] ?? 'To be announced';

                foreach ($users as $user) {
                    Message::create([
                        'user_id' => $user->id,
                        'title' => 'Team Registered: '.$race->title,
                        'body' => "Your team {$team->name} has been registered for {$race->title}.\n\nServer: {$serverName}\nPassword: {$password}\n\nSee you on track!",
                        'type' => 'event_registration',
                        'related_id' => $race->id,
                        'related_type' => Race::class,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Something went wrong while processing your registration. Please try again.');
        }

        if ($blockError) {
            return back()->with('error', $blockError);
        }

        return back()->with('success', $team->name.' has been registered for '.$race->title.'!');
    }

    public function unregisterTeam(Race $race, RaceTeamEntry $entry)
    {
        if ($race->status !== 'open') {
            return back()->with('error', 'You cannot unregister from a closed race.');
        }

        $team = auth()->user()->manageableRacingTeam();
        if (! $team) {
            return back()->with('error', 'You do not own or manage a racing team.');
        }

        if ($entry->race_id !== $race->id || $entry->racing_team_id !== $team->id) {
            return back()->with('error', 'This entry does not belong to your team.');
        }

        DB::transaction(function () use ($entry) {
            $entry->registrations()->delete();
            $entry->delete();
        });

        return back()->with('success', 'Car #'.$entry->car_number.' has been unregistered from '.$race->title.'.');
    }
}
