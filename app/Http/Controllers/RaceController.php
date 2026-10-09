<?php

namespace App\Http\Controllers;

use App\Models\Championship;
use App\Models\Driver;
use App\Models\EventTag;
use App\Models\Message;
use App\Models\Race;
use App\Models\RaceClass;
use App\Models\RaceRegistration;
use App\Models\RaceTeamEntry;
use App\Models\RacingTeam;
use App\Models\User;
use App\Services\ChampionshipRoundEntryService;
use App\Services\Contracts\ServerConfigGenerator;
use App\Services\EntryBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RaceController extends Controller
{
    // $platform is one of Race::PLATFORM_SLUGS (/events/acc-console etc.) — opens the
    // page straight on that game's event list instead of the platform picker.
    public function index(?string $platform = null)
    {
        $initialGame = $platform ? array_search($platform, Race::PLATFORM_SLUGS, true) ?: null : null;

        $races = Race::select(['id', 'title', 'game', 'track', 'scheduled_at', 'status', 'is_championship', 'event_tag', 'max_drivers', 'duration_key', 'image', 'icon', 'description', 'sr_requirement', 'min_rating', 'max_rating', 'car_class', 'weather', 'event_format_id', 'is_endurance', 'is_multiclass', 'championship_id', 'round_type'])
            ->with([
                'eventFormat:id,race1_mins,race2_mins',
                // The card's class badges (Race::displayCarClasses(), isDriverSwap()) and the
                // sign-up counter's per-class fillers (caps + requirements, Race::fillerPlan()).
                'raceClasses:id,race_id,name,car_class,sort_order,max_drivers,sr_requirement,min_rating',
            ])
            ->withIconOwners()
            // After withIconOwners(), which loads championship with only id/league_id/icon
            // — the class badges need its is_multiclass, car_class, settings and classes.
            ->with(['championship' => fn ($q) => $q->withoutTenantScope()->with('classes')])
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

        $featured = $races->groupBy('game')
            ->map(fn (Collection $gameRaces) => $this->featured($gameRaces))
            ->filter(fn (array $picks) => array_filter($picks));

        return view('race.index', compact('races', 'eventTags', 'initialGame', 'featured'));
    }

    // One game's featured row at the top of its event list (each slot null when nothing fits):
    // - weekly:  the next weekly event (Race::isWeeklyEvent()); it stays until it's finished,
    //            then the next week's takes over.
    // - special: the next special event (Race::isSpecialEvent()), likewise until finished.
    // - popular: of the other events starting in the next 24 hours (a rolling window), the one
    //            with the most real sign-ups (grid fillers don't count); ties go to the earlier.
    // $gameRaces is already non-finished and in start-time order.
    private function featured(Collection $gameRaces): array
    {
        $weekly = $gameRaces->first(fn (Race $race) => $race->isWeeklyEvent());
        $special = $gameRaces->first(fn (Race $race) => $race->isSpecialEvent());
        $taken = array_filter([$weekly?->id, $special?->id]);
        $windowEnd = now()->addDay();

        $popular = $gameRaces
            ->filter(fn (Race $race) => ! in_array($race->id, $taken, true)
                && $race->scheduled_at->isFuture() && $race->scheduled_at->lte($windowEnd)
                && $race->realSignupCount() > 0)
            ->sortByDesc(fn (Race $race) => $race->realSignupCount())
            ->first();

        return ['popular' => $popular, 'weekly' => $weekly, 'special' => $special];
    }

    public function show(Race $race)
    {
        $race->load([
            'raceClasses', 'registrations.user.roles', 'registrations.user.membership', 'registrations.raceClass', 'registrations.teamEntry.team',
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
        // (ChampionshipRoundEntryService), so this round still needs the same TEAM ENTRY card
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

        $championshipEntryFailure = null;
        // A broadcaster not racing in the championship signs up as a spectator —
        // on a driver-swap round too, where the solo registration card is otherwise hidden.
        $spectateOnly = false;
        $roundChampionship = null;

        if (auth()->check()) {
            $roundChampionship = $race->championship_id ? $race->championship()->withoutTenantScope()->first() : null;
            if ($roundChampionship) {
                $spectateOnly = auth()->user()->isBroadcaster()
                    && ! in_array(auth()->id(), $roundChampionship->driverEntrantIds());
                if (! $isTeamRace) {
                    $championshipEntryFailure = $roundChampionship->soloRoundEntryFailure(auth()->user());
                }
            }
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

        // A championship entry is in every round automatically
        // (ChampionshipRoundEntryService); a team car it took out of this round can
        // be put back in. Without an entry, a "per round" championship signs up
        // right here — the championship form, which enters every round — while a
        // "championship" one only signs up on the championship page.
        $championshipTeamCars = collect();
        $championshipSkippedCars = collect();
        $championshipTeamPending = false;
        $championshipSignupForm = false;
        if ($roundChampionship && $race->registrationOpen() && ! $spectateOnly) {
            $signsUpHere = ($roundChampionship->settings->format->team_registration_scope ?? 'per_round') === 'per_round'
                && $roundChampionship->acceptsRegistrations();

            if ($isChampionshipTeamRound) {
                $allTeamCars = $userTeam ? $roundChampionship->teamCarRegistrations($userTeam) : collect();
                $championshipTeamCars = $allTeamCars->reject->isPending()->values();
                $championshipTeamPending = $allTeamCars->isNotEmpty() && $championshipTeamCars->isEmpty();
                $enteredNumbers = $myTeamEntries->pluck('car_number')->map(fn ($n) => (int) $n)->all();
                $championshipSkippedCars = $championshipTeamCars
                    ->filter(fn ($car) => $car->car_number !== null && ! in_array((int) $car->car_number, $enteredNumbers, true))
                    ->values();
                $championshipSignupForm = $signsUpHere && $userTeam && $allTeamCars->isEmpty();
            } elseif (! $isTeamRace) {
                $championshipSignupForm = $signsUpHere && ! $isRegistered && ! $roundChampionship->isRegistered(auth()->user());
            }

            if ($championshipSignupForm) {
                $roundChampionship->load(['classes', 'league' => fn ($q) => $q->withoutTenantScope()]);
            }
        }
        $preselectedDriverIds = array_filter([$userTeam?->owner_id]);

        // Per car of the manager's team: who of its line-up + reserve sits out this round
        // — they can swap in for a driver here (swapTeamDriver()).
        $benchDrivers = collect();
        if ($isChampionshipTeamRound && $roundChampionship && $race->registrationOpen()) {
            $rounds = app(ChampionshipRoundEntryService::class);
            foreach ($myTeamEntries as $entry) {
                $car = $rounds->carRegistrationFor($entry, $roundChampionship);
                $bench = $car ? User::whereIn('id', $rounds->benchDriverIds($entry, $car))->get() : collect();
                if ($bench->isNotEmpty()) {
                    $benchDrivers[$entry->id] = $bench;
                }
            }
        }

        // Solo championship drivers not in this open round left it on purpose — every
        // other entry is in automatically.
        // (Someone on the championship's waiting list isn't racing yet, so isn't skipping.)
        $skippingDrivers = collect();
        $skippingChampionship = $race->championship_id && ! $isTeamRace && $race->status === 'open'
            ? Championship::withoutTenantScope()->find($race->championship_id)
            : null;
        if ($skippingChampionship) {
            $skippingDrivers = $skippingChampionship->registrations()->approved()->where('is_spectator', false)->whereNull('racing_team_id')
                ->whereNotIn('user_id', $race->registrations->pluck('user_id'))
                ->with('user')->get()->pluck('user')->filter()
                ->reject(fn (User $user) => $skippingChampionship->isRegistrationWaitlisted($user))
                ->values();
        }

        // Success ballast this championship round's drivers carry (EntryBalanceService),
        // heaviest first.
        $ballastByUser = app(EntryBalanceService::class)->successBallast($race);
        $successBallast = User::whereIn('id', array_keys($ballastByUser))->get()
            ->map(fn (User $user) => ['user' => $user, 'kg' => $ballastByUser[$user->id]])
            ->sortByDesc('kg')
            ->values();
        $successBallastMode = $race->championship_id
            ? ($race->championship()->withoutTenantScope()->first()?->settings->balance->success_ballast_mode ?? 'next_round')
            : null;

        $platformIds = $race->registrations->pluck('user.platform_id')->filter()->values()->all();
        $driverMap = Driver::whereIn('xuid_psid', $platformIds)
            ->get(['id', 'xuid_psid'])
            ->keyBy('xuid_psid');

        return view('race.show', compact(
            'race', 'isRegistered', 'myRegistration', 'myRegisteredAt', 'driverMap', 'userTeam', 'myTeamEntries',
            'isTeamRace', 'isChampionshipTeamRound', 'preselectedDriverIds', 'successBallast', 'successBallastMode',
            'championshipEntryFailure', 'spectateOnly', 'roundChampionship', 'championshipTeamCars', 'championshipSkippedCars',
            'championshipTeamPending', 'championshipSignupForm', 'skippingDrivers', 'benchDrivers'
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

        $championship = $race->championship_id ? $race->championship()->withoutTenantScope()->first() : null;
        if ($championship && $failure = $championship->soloRoundEntryFailure(auth()->user())) {
            return back()->with('error', $failure);
        }

        // A broadcaster who isn't racing in the championship spectates (see
        // Race::spectatorUserIds()) — no car, so no class or driver requirements.
        $asSpectator = $championship && auth()->user()->isBroadcaster()
            && ! in_array(auth()->id(), $championship->driverEntrantIds());

        if (! $asSpectator && $failure = auth()->user()->requirementFailure($race->game, $race->sr_requirement, $race->min_rating, $race->max_rating)) {
            return back()->with('error', $failure);
        }

        $raceClassId = null;

        if (! $asSpectator && $race->is_multiclass && $race->raceClasses->isNotEmpty()) {
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
            DB::transaction(function () use ($race, $raceClassId, $asSpectator, &$waitlisted) {
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
                } elseif ($asSpectator) {
                    $spectatorPassword = $config['spectatorPassword'] ?? 'To be announced';
                    Message::create([
                        'user_id' => auth()->id(),
                        'title' => 'Spectating: '.$race->title,
                        'body' => "You are registered as a spectator for {$race->title}. Join through the server's spectator slots.\n\nServer: {$serverName}\nSpectator password: {$spectatorPassword}",
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

        $waitingBefore = $this->waitlistedRegistrations($race)->keys();
        $registration->delete();

        $this->notifyPromotedDrivers($race, $waitingBefore);

        return back()->with('success', 'You have been unregistered from '.$race->title.'.');
    }

    // The Add my stream / Remove button in the event's Registration card: copies a
    // supporter's profile stream link onto their registration for this race (shown in the
    // streamers bar), or clears it again. Nothing is added automatically.
    public function updateStream(Request $request, Race $race)
    {
        $user = auth()->user();
        if (! $user->canShareStream()) {
            return back()->with('error', 'Sharing your stream is a supporter perk.');
        }

        $registration = RaceRegistration::where('race_id', $race->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $registration) {
            return back()->with('error', 'You are not registered for this race.');
        }

        if ($request->boolean('remove')) {
            $registration->update(['stream_url' => null]);

            return back()->with('success', 'Your stream has been removed from this event.');
        }

        if (! $user->memberStreamUrl()) {
            return back()->with('error', 'Add your stream link to your profile first.');
        }

        $registration->update(['stream_url' => $user->memberStreamUrl()]);

        return back()->with('success', 'Your stream is now shown on this event.');
    }

    // Registrations currently on the race's waiting list, keyed by id.
    private function waitlistedRegistrations(Race $race)
    {
        return $race->registrations()->whereNull('team_entry_id')->get()
            ->filter(fn (RaceRegistration $r) => $race->isRegistrationWaitlisted($r))
            ->keyBy('id');
    }

    // A cancelled/soft-deleted registration can free a class seat, a race-wide seat, or
    // both -- and in a multiclass race a freed race-wide seat can go to a driver in a
    // different class. Since "waitlisted" is a live rank rather than a stored flag,
    // nothing needs to be moved: this compares the waiting list before and after and
    // notifies everyone who came off it, the same way a fresh registration is
    // confirmed, so they actually know to show up.
    private function notifyPromotedDrivers(Race $race, $waitingBefore): void
    {
        $waitingAfter = $this->waitlistedRegistrations($race)->keys();

        $race->registrations()->whereIn('id', $waitingBefore->diff($waitingAfter))->get()
            ->each(fn (RaceRegistration $promoted) => $this->notifyPromotedDriver($race, $promoted));
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

        // A driver-swap championship round only takes the team's championship cars,
        // each with the line-up it registered (ChampionshipRoundEntryService) — the
        // only thing to do here is put back a car the team took out of this round.
        $championship = $race->championship_id ? $race->championship()->withoutTenantScope()->first() : null;
        if ($championship && ($championship->settings->format->driver_swaps_enabled ?? false)) {
            return $this->reenterChampionshipCar($request, $race, $championship, $team);
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

    private function reenterChampionshipCar(Request $request, Race $race, Championship $championship, RacingTeam $team)
    {
        $teamCars = $championship->teamCarRegistrations($team);
        if ($teamCars->isEmpty()) {
            return back()->with('error', 'Register your team for the championship first.');
        }

        $car = $teamCars->reject->isPending()->firstWhere('id', $request->integer('championship_registration_id'));
        if (! $car) {
            return back()->with('error', $teamCars->reject->isPending()->isEmpty()
                ? 'Your team\'s championship entry is still waiting for approval by the league.'
                : 'Pick one of your team\'s championship cars.');
        }

        $carEntries = RaceTeamEntry::withTrashed()->where('race_id', $race->id)
            ->where('racing_team_id', $team->id)->where('car_number', $car->car_number);
        if ((clone $carEntries)->exists() && ! (clone $carEntries)->onlyTrashed()->exists()) {
            return back()->with('error', 'Car #'.$car->car_number.' is already in this round.');
        }

        DB::transaction(function () use ($race, $car, $carEntries) {
            Race::where('id', $race->id)->lockForUpdate()->first();
            // Forget that the car left this round, then enter it like any other round.
            $carEntries->onlyTrashed()->forceDelete();
            app(ChampionshipRoundEntryService::class)->syncRoundEntry($car, $race);
        });

        if (! RaceTeamEntry::where('race_id', $race->id)->where('racing_team_id', $team->id)->where('car_number', $car->car_number)->exists()) {
            return back()->with('error', 'Car #'.$car->car_number.' is already taken in this round.');
        }

        return back()->with('success', 'Car #'.$car->car_number.' is back in '.$race->title.'.');
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

    // "Swap with …" next to a driver of a championship team car: for this round only,
    // the car's reserve (or the driver they replaced earlier) takes that driver's seat
    // (ChampionshipRoundEntryService::swapDriver()).
    public function swapTeamDriver(Request $request, Race $race, RaceTeamEntry $entry)
    {
        $team = auth()->user()->manageableRacingTeam();
        if (! $team || $entry->race_id !== $race->id || $entry->racing_team_id !== $team->id) {
            return back()->with('error', 'This entry does not belong to your team.');
        }
        if (! $race->registrationOpen()) {
            return back()->with('error', 'Drivers can only be swapped until shortly before the round starts.');
        }

        $championship = $race->championship_id ? Championship::withoutTenantScope()->find($race->championship_id) : null;
        $rounds = app(ChampionshipRoundEntryService::class);
        $car = $championship ? $rounds->carRegistrationFor($entry, $championship) : null;
        if (! $car) {
            return back()->with('error', 'This car has no championship line-up to swap from.');
        }

        $outId = $request->integer('driver_id');
        $inId = $request->integer('with_id');
        if (! $entry->registrations()->where('user_id', $outId)->exists() || ! in_array($inId, $rounds->benchDriverIds($entry, $car), true)) {
            return back()->with('error', 'That swap is not possible for this car.');
        }

        $rounds->swapDriver($entry, $outId, $inId);

        $names = User::whereIn('id', [$outId, $inId])->get()->keyBy('id');

        return back()->with('success', $names[$inId]->displayName().' drives car #'.$entry->car_number.' instead of '.$names[$outId]->displayName().' in '.$race->title.'.');
    }
}
