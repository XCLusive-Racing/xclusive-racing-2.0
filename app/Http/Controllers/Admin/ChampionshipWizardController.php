<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Championship\ApproveChampionshipRatingRequest;
use App\Http\Requests\Championship\PublishChampionshipRequest;
use App\Http\Requests\Championship\SaveChampionshipStepRequest;
use App\Jobs\PushRoundConfigJob;
use App\Models\Championship;
use App\Models\ChampionshipDriverClass;
use App\Models\ChampionshipRegistration;
use App\Models\FtpServer;
use App\Models\League;
use App\Models\PointsScheme;
use App\Models\Race;
use App\Models\SessionFormat;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ChampionshipTeamEntryService;
use App\Services\PracticeServer\ChampionshipPracticeService;
use App\Settings\ChampionshipSettingsSchema;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ChampionshipWizardController extends Controller
{
    // Entry point for "Championships" in the Leagues nav — lets a manager who
    // belongs to more than one league pick which one they're creating for.
    // Someone in exactly one league skips straight past this, same as the
    // Leagues index does for admin.leagues.edit.
    public function selectLeague(Request $request)
    {
        Gate::authorize('viewAny', Championship::class);

        $user = $request->user();
        $leagues = $user->canManage()
            ? League::withoutTenantScope()->orderBy('name')->get()
            : League::orderBy('name')->get();

        if ($leagues->count() === 1) {
            return redirect()->route('admin.leagues.championships.index', $leagues->first());
        }

        return view('admin.leagues.championships.select', compact('leagues'));
    }

    public function index(Request $request, League $league)
    {
        $user = $request->user();
        abort_unless($user->canManage() || $user->managesLeague($league) || $user->stewardsLeague($league), 403);

        $championships = $league->championships()->orderBy('created_at', 'desc')->get();

        return view('admin.leagues.championships.index', compact('league', 'championships'));
    }

    public function store(Request $request, League $league)
    {
        Gate::authorize('create', [Championship::class, $league]);

        $championship = Championship::create([
            'league_id' => $league->id,
            'name' => 'New Championship',
            'game' => 'acc',
            'season' => now()->year,
            'status' => 'draft',
            'visibility' => 'public',
            'settings' => ChampionshipSettingsSchema::defaults(),
        ]);

        AuditLogger::record($request->user(), $championship, 'championship.created', null, $league->id);

        return redirect()->route('admin.leagues.championships.wizard', [$league, $championship, 'basics']);
    }

    // User-directed 2026-09: "add the option to be able to remove your own
    // championships in the admin panel championships overview" -- there was
    // previously no way to remove one at all, even though ChampionshipPolicy
    // already had a delete() gate (canManage() or the league's own manager)
    // sitting unused. A soft delete, like League's own archive() -- reversible,
    // and rounds/registrations/results stay intact and simply hang off a
    // trashed parent rather than being destroyed outright.
    public function destroy(Request $request, League $league, Championship $championship)
    {
        $this->assertLeagueOfInterest($league, $championship);
        Gate::authorize('delete', $championship);

        $name = $championship->name;
        $championship->delete();

        AuditLogger::record($request->user(), $championship, 'championship.deleted', null, $league->id);

        return redirect()->route('admin.leagues.championships.index', $league)->with('success', $name.' has been removed.');
    }

    // Round fields a duplicated season carries over — the track, sessions and
    // conditions; never results, push status or the date (shifted instead).
    private const DUPLICATED_ROUND_FIELDS = [
        'track', 'round_number', 'round_type', 'is_multiclass', 'is_endurance', 'practice_duration', 'qualifying_duration',
        'race_duration', 'race_durations', 'session_format_id', 'event_format_id', 'weather', 'weather_randomness',
        'rain_level', 'time_of_day', 'ambient_temp', 'practice_time_multiplier', 'qualifying_time_multiplier',
        'race_time_multiplier', 'description', 'image', 'icon', 'xcl_r_multiplier', 'pitstop_count', 'min_stop_secs',
        'tyre_set_count', 'driver_stint_time_mins', 'max_total_driving_time_mins', 'mandatory_driver_swap',
        'config_overrides', 'has_practice_server', 'practice_notes', 'ftp_server_id',
    ];

    public function duplicateForm(Request $request, League $league, Championship $championship)
    {
        $this->assertLeagueOfInterest($league, $championship);
        Gate::authorize('view', $championship);
        Gate::authorize('create', [Championship::class, $league]);

        $rounds = $championship->rounds()->get();
        // Suggested start: a week after the last round, same weekday and time.
        $suggestedStart = $rounds->isNotEmpty()
            ? $rounds->max('scheduled_at')->copy()->tz('Europe/London')->addWeek()->format('Y-m-d\TH:i')
            : null;

        return view('admin.leagues.championships.duplicate', compact('league', 'championship', 'rounds', 'suggestedStart'));
    }

    // A new season from an existing championship: every setting, the classes and
    // (optionally) the rounds, shifted so the first one starts on the chosen date
    // and the gaps between them stay the same. Entries, results, penalties and
    // the XCL rating approval start over; the copy is a draft.
    public function duplicate(Request $request, League $league, Championship $championship)
    {
        $this->assertLeagueOfInterest($league, $championship);
        Gate::authorize('view', $championship);
        Gate::authorize('create', [Championship::class, $league]);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'season' => 'required|integer|min:2000|max:2100',
            'copy_rounds' => 'nullable|boolean',
            'first_round_at' => 'required_if:copy_rounds,1|nullable|date_format:Y-m-d\TH:i|after:now',
        ]);

        $copyRounds = $request->boolean('copy_rounds') && $championship->rounds()->exists();
        $roundIssues = ['without_server' => 0, 'skipped' => 0];

        $copy = DB::transaction(function () use ($championship, $league, $data, $copyRounds, &$roundIssues) {
            $settings = $championship->settings->toArray();
            $settings['requirements']['registration_opens_at'] = null;
            $settings['requirements']['registration_closes_at'] = null;
            $settings['schedule']['start_date'] = $copyRounds ? substr($data['first_round_at'], 0, 10) : null;
            // Last season's per-driver ballast/restrictor adjustments were for last season.
            $settings['balance']['adjustments'] = [];

            $copy = Championship::create(array_merge(
                $championship->only([
                    'tagline', 'slogan', 'game', 'platform', 'visibility', 'description', 'image', 'icon',
                    'max_drivers', 'is_multiclass', 'car_class', 'points_system', 'bonus_fastest_lap', 'bonus_pole',
                    'ftp_server_id',
                ]),
                [
                    'league_id' => $league->id,
                    'name' => $data['name'],
                    'season' => $data['season'],
                    'status' => 'draft',
                    'registration_open' => false,
                    'settings' => $settings,
                ]
            ));

            foreach ($championship->classes()->get() as $class) {
                $copy->classes()->create($class->only(['name', 'color', 'car_class', 'max_drivers', 'sr_requirement', 'min_rating', 'sort_order']));
            }

            // The classes carry over; who is in which class is decided again.
            foreach ($championship->driverClasses()->get() as $class) {
                $copy->driverClasses()->create($class->only(['name', 'acc_category', 'max_entries', 'sort_order']));
            }

            if ($copyRounds) {
                $roundIssues = $this->duplicateRounds($championship, $copy, $league, $data['first_round_at']);
            }

            return $copy;
        });

        AuditLogger::record($request->user(), $copy, 'championship.duplicated', ['from' => $championship->id], $league->id);

        return redirect()->route('admin.leagues.championships.wizard', [$league, $copy, 'basics'])
            ->with('success', trim($championship->name.' duplicated as a draft.'
                .($roundIssues['without_server'] ? ' '.$roundIssues['without_server'].' '.Str::plural('round', $roundIssues['without_server']).' lost their server (slot taken or not valid) — pick one on the Rounds step.' : '')
                .($roundIssues['skipped'] ? ' '.$roundIssues['skipped'].' '.Str::plural('round', $roundIssues['skipped']).' could not be copied (start time not on the hour or half hour).' : '')));
    }

    // Copies $from's rounds into $to, the first one on $firstRoundAt (UK time) and
    // the rest keeping their gaps. Shifted in UK wall-clock time, so a 20:00 round
    // stays 20:00 across a clock change. A round whose server slot is taken or not
    // valid is still copied, without the server; one that still isn't valid (an old
    // off-slot start time) is skipped. Returns how many of each.
    private function duplicateRounds(Championship $from, Championship $to, League $league, string $firstRoundAt): array
    {
        $rounds = $from->rounds()->reorder('scheduled_at')->get();
        $wallClock = fn (Race $race) => Carbon::parse($race->scheduled_at->copy()->tz('Europe/London')->format('Y-m-d H:i'), 'UTC');
        $shift = $wallClock($rounds->first())->diffInMinutes(Carbon::parse($firstRoundAt, 'UTC'), false);

        $claimedSlots = [];
        $issues = ['without_server' => 0, 'skipped' => 0];

        foreach ($rounds as $round) {
            $row = $round->only(self::DUPLICATED_ROUND_FIELDS);
            $row['scheduled_at'] = $wallClock($round)->addMinutes((int) $shift)->format('Y-m-d\TH:i');

            $result = $this->resolveRoundRow($row, $to, $league, $claimedSlots);
            if (is_string($result) && ! empty($row['ftp_server_id'])) {
                $row['ftp_server_id'] = null;
                $result = $this->resolveRoundRow($row, $to, $league, $claimedSlots);
                $issues['without_server']++;
            }

            is_array($result) ? Race::create($result) : $issues['skipped']++;
        }

        return $issues;
    }

    public function edit(Request $request, League $league, Championship $championship, string $step)
    {
        $this->assertLeagueOfInterest($league, $championship);
        Gate::authorize('view', $championship);
        abort_unless(array_key_exists($step, ChampionshipSettingsSchema::STEPS), 404);

        $pointsSchemes = PointsScheme::orderByDesc('is_template')->orderBy('name')->get();

        // Only needed by the Basics step's Server dropdown, but cheap enough to
        // always pass — scoped to this league's own active servers, same as
        // roundCreate() below.
        $servers = $league->ftpServers()->where('active', true)->orderBy('name')->get();

        return view('admin.leagues.championships.wizard', [
            'league' => $league,
            'championship' => $championship,
            'step' => $step,
            'steps' => ChampionshipSettingsSchema::STEPS,
            'fields' => ChampionshipSettingsSchema::fieldsForStep($step),
            'pointsSchemes' => $pointsSchemes,
            'servers' => $servers,
            'canApproveRating' => $request->user()->can('approveRating', $championship),
        ]);
    }

    public function update(SaveChampionshipStepRequest $request, League $league, Championship $championship, string $step)
    {
        $this->assertLeagueOfInterest($league, $championship);
        abort_unless(array_key_exists($step, ChampionshipSettingsSchema::STEPS) && $step !== 'review', 404);

        $before = $step === 'basics'
            ? $championship->only(['name', 'tagline', 'slogan', 'slug', 'game', 'platform', 'visibility'])
            : $championship->settings->toArray();

        if ($step === 'basics') {
            $data = $request->validated();
            $data['image'] = $this->resolveMedia($request, 'image', 'images/championships');
            $data['icon'] = $this->resolveMedia($request, 'icon', 'images/icons');
            unset($data['image_path'], $data['icon_path']);

            // Nested settings.schedule.* comes along in validated() too (it rides
            // on this same step) — applyStepSettings() below is what actually
            // merges that into the settings blob; passing the raw partial array
            // straight to update() would blow away every other settings group.
            unset($data['settings']);

            if (! empty($data['ftp_server_id'])) {
                $server = $league->ftpServers()->find($data['ftp_server_id']);
                abort_unless($server, 403, 'That server does not belong to this league.');

                if (! $server->supportsRaceGame($data['game'])) {
                    return back()->withInput()->withErrors(['ftp_server_id' => FtpServer::ERR_WRONG_PLATFORM]);
                }
            }

            $gameChanged = $data['game'] !== $championship->game;
            if ($gameChanged && ! $championship->canChangeGame()) {
                return back()->withInput()->withErrors(['game' => Championship::ERR_GAME_LOCKED]);
            }

            $unassignedRounds = DB::transaction(function () use ($championship, $data, $gameChanged) {
                $championship->update($data);

                return $gameChanged ? $championship->syncRoundsToGame() : 0;
            });
            $this->applyStepSettings($request, $championship, 'basics');
        } else {
            $this->applyStepSettings($request, $championship, $step);
        }

        AuditLogger::record($request->user(), $championship, 'championship.step_saved', ['step' => $step, 'before' => $before]);

        $next = $this->nextStep($step);

        $message = ucfirst($step).' saved.';
        if (($unassignedRounds ?? 0) > 0) {
            $message .= ' '.$unassignedRounds.' round(s) were on a server of the other ACC platform and no longer have a server — pick a new one per round.';
        }

        return redirect()->route('admin.leagues.championships.wizard', [$league, $championship, $next])
            ->with('success', $message);
    }

    public function roundCreate(Request $request, League $league, Championship $championship)
    {
        $this->assertLeagueOfInterest($league, $championship);
        Gate::authorize('update', $championship);

        // Scoped to this league's own assigned servers only — a league manager
        // must never be able to push a round's config to another league's server.
        $servers = $this->roundServers($league, $championship);
        $sessionFormats = SessionFormat::where('league_id', $league->id)->orderBy('name')->get();

        $nextRoundNumber = $championship->rounds()->max('round_number') + 1;
        $suggestedScheduledAt = $championship->scheduledDateTimeForRound($nextRoundNumber);

        // Bulk mode (below) needs a suggestion per generated row, not just the next
        // one — reuses the exact same recurrence math a single Add Round already
        // suggests from, so there's only one place that logic lives.
        $bulkSuggestions = [];
        for ($i = 0; $i < 20; $i++) {
            $suggestion = $championship->scheduledDateTimeForRound($nextRoundNumber + $i);
            $bulkSuggestions[] = $suggestion?->format('Y-m-d\TH:i');
        }

        return view('admin.leagues.championships.round-create', compact(
            'league', 'championship', 'servers', 'suggestedScheduledAt', 'nextRoundNumber', 'bulkSuggestions', 'sessionFormats'
        ));
    }

    public function addRound(Request $request, League $league, Championship $championship)
    {
        $this->assertLeagueOfInterest($league, $championship);
        Gate::authorize('update', $championship);

        $data = $this->withRoundDefaults($request, $request->validate($this->singleRoundRules()));

        $claimedSlots = [];
        $result = $this->resolveRoundRow($data, $championship, $league, $claimedSlots);

        if (is_string($result)) {
            return back()->withInput()->withErrors(['scheduled_at' => $result]);
        }

        $race = Race::create($result);
        $this->syncTeamEntriesForNewRound($championship, $race);

        AuditLogger::record($request->user(), $championship, 'championship.round_added', ['title' => $result['title']]);

        return redirect()->route('admin.leagues.championships.wizard', [$league, $championship, 'rounds'])
            ->with('success', 'Round added.');
    }

    public function roundEdit(Request $request, League $league, Championship $championship, Race $race)
    {
        $this->assertLeagueOfInterest($league, $championship);
        Gate::authorize('update', $championship);
        abort_unless($race->championship_id === $championship->id, 404);

        $servers = $this->roundServers($league, $championship);
        $sessionFormats = SessionFormat::where('league_id', $league->id)->orderBy('name')->get();

        return view('admin.leagues.championships.round-edit', compact('league', 'championship', 'race', 'servers', 'sessionFormats'));
    }

    // Same validity rules Add Round enforces (resolveRoundRow()), except the
    // slot-collision check excludes this round's own current slot — otherwise
    // editing a round without changing its time would reject against itself.
    public function updateRound(Request $request, League $league, Championship $championship, Race $race)
    {
        $this->assertLeagueOfInterest($league, $championship);
        Gate::authorize('update', $championship);
        abort_unless($race->championship_id === $championship->id, 404);

        $data = $this->withRoundDefaults($request, $request->validate($this->singleRoundRules()));

        $claimedSlots = [];
        $result = $this->resolveRoundRow($data, $championship, $league, $claimedSlots, $race->id);

        if (is_string($result)) {
            return back()->withInput()->withErrors(['scheduled_at' => $result]);
        }

        // resolveRoundRow() is shared with round *creation*, where a race always
        // starts 'open' — an edit must never reset a round that's already
        // running/finished back to 'open'.
        unset($result['status']);

        // Switching servers (or dropping one) leaves any prior push status behind
        // it — carrying it over would misreport a config as pushed to a server it
        // was never actually sent to.
        if (($result['ftp_server_id'] ?? null) !== $race->ftp_server_id) {
            $result['config_push_status'] = $result['ftp_server_id'] ? 'pending' : null;
        }

        $race->update($result);

        AuditLogger::record($request->user(), $championship, 'championship.round_updated', ['race_id' => $race->id]);

        return redirect()->route('admin.leagues.championships.wizard', [$league, $championship, 'rounds'])
            ->with('success', 'Round updated.');
    }

    // "Bulk" the same way the race wizard's Bulk Schedule tab is: generate a run of
    // rounds from one shared set of session/weather/server settings, each only
    // needing its own track and date — reusing resolveRoundRow() per row so the
    // exact same slot/validity rules single-round Add Round already enforces
    // apply here too. All-or-nothing: one bad row rejects the whole batch rather
    // than creating half of it, same convention bulkStore() uses for races.
    public function bulkAddRounds(Request $request, League $league, Championship $championship)
    {
        $this->assertLeagueOfInterest($league, $championship);
        Gate::authorize('update', $championship);

        $data = $this->withRoundDefaults($request, $request->validate($this->sharedRoundRules() + [
            'rounds' => 'required|array|min:1',
            'rounds.*.track' => 'required|string|max:255',
            'rounds.*.scheduled_at' => 'required|date',
            'rounds.*.round_number' => 'nullable|integer|min:1',
        ]));

        $shared = collect($data)->except('rounds')->all();
        $rows = [];
        $claimedSlots = [];

        // resolveRoundRow() auto-numbers a round from the DB's current max when
        // none is given — fine for a single Add Round, but every row in this
        // batch would read the same stale max before any of them are actually
        // persisted. Numbered here instead, once, so they land 1, 2, 3…
        $autoRoundNumber = $championship->rounds()->max('round_number') + 1;

        foreach ($data['rounds'] as $i => $row) {
            if (empty($row['round_number'])) {
                $row['round_number'] = $autoRoundNumber++;
            }

            $result = $this->resolveRoundRow(array_merge($shared, $row), $championship, $league, $claimedSlots);

            if (is_string($result)) {
                return back()->withInput()->withErrors(['rounds' => 'Row '.($i + 1).' ('.($row['track'] ?: '—').'): '.$result]);
            }

            $rows[] = $result;
        }

        DB::transaction(function () use ($rows, $championship) {
            foreach ($rows as $row) {
                $race = Race::create($row);
                $this->syncTeamEntriesForNewRound($championship, $race);
            }
        });

        AuditLogger::record($request->user(), $championship, 'championship.rounds_bulk_added', ['count' => count($rows)]);

        return redirect()->route('admin.leagues.championships.wizard', [$league, $championship, 'rounds'])
            ->with('success', count($rows).' rounds added.');
    }

    // "Championship"-scope team registrations (settings.format.team_registration_scope,
    // see ChampionshipTeamEntryService) carried a car number/model/starting driver
    // once instead of re-registering every round — a round added after the fact
    // still needs its own RaceTeamEntry generated from that, which is what this does.
    private function syncTeamEntriesForNewRound(Championship $championship, Race $race): void
    {
        $registrations = ChampionshipRegistration::where('championship_id', $championship->id)
            ->approved()
            ->whereNotNull('racing_team_id')
            ->whereNotNull('car_number')
            ->get();

        if ($registrations->isEmpty()) {
            return;
        }

        $service = app(ChampionshipTeamEntryService::class);
        foreach ($registrations as $registration) {
            $service->syncRoundEntry($registration, $race);
        }
    }

    // The session/weather/server fields every round form shares — Add Round,
    // Edit Round and Bulk Add Rounds.
    private function sharedRoundRules(): array
    {
        return [
            'practice_duration' => 'nullable|integer|min:1|max:999',
            'qualifying_duration' => 'nullable|integer|min:1|max:999',
            'race_duration' => 'nullable|integer|min:1|max:999',
            'race_lengths' => ['nullable', 'string', 'regex:'.SessionFormatController::RACE_LENGTHS_REGEX],
            'session_format_id' => 'nullable|integer',
            'weather' => 'nullable|in:dry,wet,mixed,random',
            'weather_randomness' => 'nullable|in:0,1,2,3,4,5,6,7,random',
            'rain_level' => 'nullable|numeric|min:0|max:1',
            'time_of_day' => 'nullable|date_format:H:i',
            'ambient_temp' => 'nullable|integer|min:-30|max:50',
            'description' => 'nullable|string',
            'xcl_r_multiplier' => 'nullable|numeric|min:0.6|max:2.5',
            'pitstop_count' => 'nullable|integer|min:0|max:9',
            'tyre_set_count' => 'nullable|integer|min:1|max:50',
            'fixed_stop_time' => 'nullable|boolean',
            'driver_stint_time_mins' => 'nullable|integer|min:1|max:1440',
            'max_total_driving_time_mins' => 'nullable|integer|min:1|max:1440',
            'mandatory_driver_swap' => 'nullable|boolean',
            // Checked against the league's own list in resolveRoundType().
            'round_type' => 'nullable|string|max:50',
            'round_type_new' => 'nullable|string|max:50',
            // Ownership (this league's own servers only) is checked in resolveRoundRow() —
            // a plain exists:ftp_servers,id can't scope that.
            'ftp_server_id' => 'nullable|exists:ftp_servers,id',
        ];
    }

    private function singleRoundRules(): array
    {
        return [
            'track' => 'required|string|max:255',
            'scheduled_at' => 'required|date',
            'round_number' => 'nullable|integer|min:1',
        ] + $this->sharedRoundRules();
    }

    private function withRoundDefaults(Request $request, array $data): array
    {
        $data['mandatory_driver_swap'] = $request->boolean('mandatory_driver_swap');
        // Plain on/off, no admin-entered number — 25s fixed when on, dynamic
        // (null) when off. Same values applyStepSettings() derives for the
        // championship-wide Sessions-step default.
        $data['min_stop_secs'] = $request->boolean('fixed_stop_time') ? 25 : null;

        return $data;
    }

    // The round's type: one of the league's options, or — picked "+ Add type…" —
    // a new name, which joins the league's list for every later round. False
    // when it's neither.
    private function resolveRoundType(?string $picked, ?string $newName, League $league): string|null|false
    {
        if ($picked === null || $picked === '') {
            return null;
        }

        if ($picked !== Race::NEW_ROUND_TYPE) {
            return in_array($picked, $league->roundTypeOptions(), true) ? $picked : false;
        }

        $name = trim((string) $newName);
        if ($name === '') {
            return false;
        }

        // Same name in another case is the same type, not a second one.
        $existing = collect($league->roundTypeOptions())->first(fn ($option) => mb_strtolower($option) === mb_strtolower($name));
        if ($existing !== null) {
            return $existing;
        }

        return $league->roundTypes()->create(['name' => $name])->name;
    }

    // Shared by addRound() and bulkAddRounds() — turns one row's raw
    // track/scheduled_at/round_number plus the shared session/weather/server
    // fields into a finalized Race::create() payload, or returns a plain error
    // string on the same validity rules single-round Add Round already enforced
    // (server ownership, whole-hour start, slot validity/availability).
    // $claimedSlots accumulates this batch's own slot times so two rows in the
    // same bulk submission can't collide with each other either, not just with
    // rounds already in the database.
    private function resolveRoundRow(array $data, Championship $championship, League $league, array &$claimedSlots, ?int $excludeRaceId = null): array|string
    {
        if (! empty($data['ftp_server_id']) && ! $league->ftpServers()->where('id', $data['ftp_server_id'])->exists()) {
            abort(403, 'That server does not belong to this league.');
        }

        // The race format picked (if any) only prefilled the form client-side — kept
        // for reference, and only one of this league's own.
        if (! empty($data['session_format_id']) && ! SessionFormat::where('league_id', $league->id)->whereKey($data['session_format_id'])->exists()) {
            abort(403, 'That race format does not belong to this league.');
        }

        if (isset($data['track'])) {
            $data['track'] = Race::resolveTrack($data['track']);
        }

        if (array_key_exists('round_type', $data)) {
            $roundType = $this->resolveRoundType($data['round_type'], $data['round_type_new'] ?? null, $league);
            if ($roundType === false) {
                return 'Pick a round type from the list, or enter a name for the new one.';
            }
            $data['round_type'] = $roundType;
        }
        unset($data['round_type_new']);

        // "Races (min)": "25" is the usual single race, "25, 25" a multi-race round —
        // race_duration keeps the first race's length either way.
        if (array_key_exists('race_lengths', $data)) {
            $lengths = Race::parseRaceLengths($data['race_lengths']);
            if ($lengths) {
                $data['race_duration'] = $lengths[0];
                $data['race_durations'] = count($lengths) > 1 ? $lengths : null;
            }
            unset($data['race_lengths']);
        }

        // Rain level is only meaningful for wet/mixed weather — drop a stray value
        // otherwise, same reasoning as RaceController::normalizeRainLevel().
        if (! in_array($data['weather'] ?? null, ['wet', 'mixed'], true)) {
            $data['rain_level'] = null;
        }

        $data['championship_id'] = $championship->id;
        $data['game'] = $championship->game;
        $data['car_class'] = $championship->car_class;
        $data['max_drivers'] = $championship->max_drivers;
        $data['status'] = 'open';
        $data['is_championship'] = true;
        $data['event_tag'] = 'championship';
        $data['scheduled_at'] = Carbon::createFromFormat('Y-m-d\TH:i', $data['scheduled_at'], 'Europe/London')->utc();

        // Rounds start on the hour or half hour only — the datetime picker already
        // restricts this client-side, but a raw request could still smuggle in something else.
        if (! in_array($data['scheduled_at']->minute, [0, 30], true)) {
            return 'Rounds can only start on the hour or half hour.';
        }

        if (empty($data['round_number'])) {
            $data['round_number'] = $championship->rounds()->max('round_number') + 1;
        }

        // Two rounds sharing a number would share a title and muddle the standings'
        // round columns. (Bulk rows number themselves before they get here.)
        if ($championship->rounds()->where('round_number', $data['round_number'])
            ->when($excludeRaceId, fn ($q) => $q->whereKeyNot($excludeRaceId))->exists()) {
            return 'Round '.$data['round_number'].' already exists in this championship.';
        }

        // "Title" is chosen once at Basics (the championship's Name) and composed
        // with the round number here — a manager never re-types it per round.
        $data['title'] = $championship->name.' — Round '.$data['round_number'];

        if (! empty($data['ftp_server_id'])) {
            $server = FtpServer::find($data['ftp_server_id']);
            $slotKey = $data['scheduled_at']->format('Y-m-d H:i');

            if ($server && ! $server->supportsRaceGame($championship->game)) {
                return FtpServer::ERR_WRONG_PLATFORM;
            }
            if ($server && ! $server->isValidSlot($data['scheduled_at'], allowHalfHour: true)) {
                return 'That time is not a valid slot on this server.';
            }
            if ($server && (in_array($slotKey, $server->takenSlots($excludeRaceId), true) || in_array($slotKey, $claimedSlots, true))) {
                return 'That slot is already taken on this server.';
            }

            $claimedSlots[] = $slotKey;
            $data['slot_time'] = $data['scheduled_at']->copy();
            $data['config_push_status'] = 'pending';
        } else {
            $data['slot_time'] = null;
        }

        return $data;
    }

    public function removeRound(Request $request, League $league, Championship $championship, Race $race)
    {
        $this->assertLeagueOfInterest($league, $championship);
        Gate::authorize('update', $championship);
        abort_unless($race->championship_id === $championship->id, 404);

        // A round that never ran is deleted — detached, it would linger as an orphaned
        // "championship" event nobody manages. One with results keeps them (ratings
        // were applied from it) and becomes a plain standalone race; its event_tag
        // falls back the same way any race's does (format default, else 'daily').
        if ($race->results()->exists()) {
            $race->update([
                'championship_id' => null, 'round_number' => null, 'is_championship' => false,
                'event_tag' => $race->eventFormat?->default_event_tag ?? 'daily',
            ]);
        } else {
            DB::transaction(function () use ($race) {
                $race->registrations()->delete();
                $race->teamEntries()->delete();
                $race->delete();
            });
        }

        AuditLogger::record($request->user(), $championship, 'championship.round_removed', ['race_id' => $race->id]);

        return redirect()->route('admin.leagues.championships.wizard', [$league, $championship, 'rounds'])
            ->with('success', 'Round removed.');
    }

    // Manual "push now" for a league's own round — reuses the exact push pipeline
    // XCL's own scheduled push uses (Phase 3, docs/championships/PLAN.md), just
    // queued instead of synchronous since this is triggered by a less-trusted user,
    // on demand, and shouldn't block the request on an FTP round-trip.
    public function pushRoundConfig(Request $request, League $league, Championship $championship, Race $race)
    {
        $this->assertLeagueOfInterest($league, $championship);
        Gate::authorize('update', $championship);
        abort_unless($race->championship_id === $championship->id, 404);
        abort_unless($race->ftp_server_id, 404);

        Race::where('id', $race->id)->update(['config_push_status' => 'pending']);

        PushRoundConfigJob::dispatch($race->id);

        AuditLogger::record($request->user(), $championship, 'championship.round_config_push_queued', ['race_id' => $race->id]);

        return back()->with('success', 'Config push queued for '.$race->title.'.');
    }

    // "Push now" for the 24h practice server — same push the midnight run does, e.g.
    // right after switching it on instead of waiting for the night.
    public function pushPractice(Request $request, League $league, Championship $championship, ChampionshipPracticeService $practice)
    {
        $this->assertLeagueOfInterest($league, $championship);
        Gate::authorize('update', $championship);

        $round = $practice->nextRound($championship);
        if (! $round) {
            return back()->with('error', 'There is no upcoming round to practise for.');
        }

        $server = $practice->serverFor($championship, $round);
        if (! $server) {
            return back()->with('error', 'Round '.$round->round_number.' has no active server (and the championship has no default server) to run practice on.');
        }

        $error = $practice->push($championship, $round, $server);

        AuditLogger::record($request->user(), $championship, 'championship.practice_pushed', ['race_id' => $round->id, 'error' => $error]);

        return $error
            ? back()->with('error', 'Practice push failed: '.$error)
            : back()->with('success', 'Practice for '.$round->track.' pushed to '.$server->name.'.');
    }

    public function publish(PublishChampionshipRequest $request, League $league, Championship $championship)
    {
        $this->assertLeagueOfInterest($league, $championship);

        $championship->update(['status' => 'published']);

        AuditLogger::record($request->user(), $championship, 'championship.published');

        return redirect()->route('admin.leagues.championships.wizard', [$league, $championship, 'review'])
            ->with('success', $championship->name.' has been published.');
    }

    public function openRegistration(Request $request, League $league, Championship $championship)
    {
        $this->assertLeagueOfInterest($league, $championship);
        Gate::authorize('update', $championship);
        abort_unless(in_array($championship->status, ['published', 'registration_closed'], true), 404);

        $championship->update(['status' => 'registration_open', 'registration_open' => true]);

        AuditLogger::record($request->user(), $championship, 'championship.registration_opened');

        return back()->with('success', 'Registration is now open for '.$championship->name.'.');
    }

    public function closeRegistration(Request $request, League $league, Championship $championship)
    {
        $this->assertLeagueOfInterest($league, $championship);
        Gate::authorize('update', $championship);
        abort_unless($championship->status === 'registration_open', 404);

        $championship->update(['status' => 'registration_closed', 'registration_open' => false]);

        AuditLogger::record($request->user(), $championship, 'championship.registration_closed');

        return back()->with('success', 'Registration is now closed for '.$championship->name.'.');
    }

    // User-directed 2026-09: pulls an already-published championship out of
    // the public championships listing and public Events page
    // (Championship::scopePubliclyVisible(), used by both) without touching
    // its status -- registrations, rounds and standings all stay exactly as
    // they are, unlike reverting to draft. Only makes sense once published:
    // a draft championship is already excluded from both by its status alone.
    public function hide(Request $request, League $league, Championship $championship)
    {
        $this->assertLeagueOfInterest($league, $championship);
        Gate::authorize('update', $championship);
        abort_unless($championship->status !== 'draft', 404);

        $championship->update(['visibility' => 'unlisted']);

        AuditLogger::record($request->user(), $championship, 'championship.hidden');

        return back()->with('success', $championship->name.' is now hidden from public listings.');
    }

    public function unhide(Request $request, League $league, Championship $championship)
    {
        $this->assertLeagueOfInterest($league, $championship);
        Gate::authorize('update', $championship);

        $championship->update(['visibility' => 'public']);

        AuditLogger::record($request->user(), $championship, 'championship.unhidden');

        return back()->with('success', $championship->name.' is public again.');
    }

    // The only route that can ever turn xcl_rating_enabled on. Authorization is
    // enforced by ApproveChampionshipRatingRequest itself (policy-gated, same
    // gate as update() — a league manager can reach this for their own
    // championship, from the Basics step's own toggle), not just here.
    public function approveRating(ApproveChampionshipRatingRequest $request, League $league, Championship $championship)
    {
        $this->assertLeagueOfInterest($league, $championship);

        $championship->approveXclRating($request->user());

        AuditLogger::record($request->user(), $championship, 'championship.rating_approved', [
            'approved_by' => $request->user()->id,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'xcl_rating_enabled' => true]);
        }

        return back()->with('success', 'XCL Rating approved for '.$championship->name.'.');
    }

    public function revokeRating(Request $request, League $league, Championship $championship)
    {
        $this->assertLeagueOfInterest($league, $championship);
        Gate::authorize('approveRating', $championship);

        $championship->revokeXclRating();

        AuditLogger::record($request->user(), $championship, 'championship.rating_revoked');

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'xcl_rating_enabled' => false]);
        }

        return back()->with('success', 'XCL Rating revoked for '.$championship->name.'.');
    }

    // A championship's URL is nested under a league, but a canManage() user
    // bypasses the tenant scope entirely and could otherwise reach this action
    // with mismatched {league}/{championship} ids — guard the pairing directly.
    private function assertLeagueOfInterest(League $league, ?Championship $championship = null): void
    {
        if ($championship) {
            abort_unless($championship->league_id === $league->id, 404);
        }
    }

    // The league's active servers a round of this championship can actually run
    // on — ACC PC rounds only PC servers, ACC Console rounds only console ones
    // (FtpServer::supportsRaceGame(), also enforced on save in resolveRoundRow()).
    private function roundServers(League $league, Championship $championship): Collection
    {
        return $league->ftpServers()->where('active', true)->orderBy('name')->get()
            ->filter(fn (FtpServer $server) => $server->supportsRaceGame($championship->game))
            ->values();
    }

    private function nextStep(string $step): string
    {
        $order = array_keys(ChampionshipSettingsSchema::STEPS);
        $index = array_search($step, $order, true);

        return $order[$index + 1] ?? 'review';
    }

    private function applyStepSettings(Request $request, Championship $championship, string $step): void
    {
        $groups = ChampionshipSettingsSchema::STEP_GROUPS[$step] ?? [];
        $settings = $championship->settings->toArray();
        $input = $request->input('settings', []);

        foreach ($groups as $group) {
            $settings[$group] = array_merge($settings[$group] ?? [], $input[$group] ?? []);

            foreach (ChampionshipSettingsSchema::fieldsForGroup($group) as $field) {
                if ($field['type'] === 'boolean') {
                    $settings[$group][$field['key']] = $request->boolean("settings.{$group}.{$field['key']}");
                }
            }
        }

        // Fixed Stop Time is a plain on/off button, no admin-entered number
        // (user-directed) — min_stop_secs is always derived from it: 25 when
        // on, dynamic (null) when off. Same derivation Add/Edit Round applies
        // to the per-round override.
        if ($step === 'sessions') {
            $settings['sessions']['min_stop_secs'] = ($settings['sessions']['fixed_stop_time'] ?? false) ? 25 : null;
        }

        // Repeatable list fields ride in as pre-built JSON, the same pattern the
        // race wizard uses for its class picker (a hidden input, built by JS).
        if (in_array('format', $groups, true) && $request->filled('classes_json')) {
            $settings['format']['classes'] = $this->decodeList($request->input('classes_json'), ['name', 'eligible_cars', 'max_entries']);
        }

        if (in_array('balance', $groups, true) && $request->filled('adjustments_json')) {
            $settings['balance']['adjustments'] = $this->decodeList($request->input('adjustments_json'), ['scope', 'target', 'ballast_kg', 'restrictor_percent']);
        }

        $championship->settings = $settings;

        // Car class and driver cap are championship-wide and set once here — every
        // round reads them from the championship itself rather than asking again,
        // and registration (isFull(), requirementFailure()) already reads these
        // same two real columns, not the settings blob. is_multiclass is the real
        // column the public registration flow (ChampionshipController::register())
        // branches on — settings.format.multiclass_enabled alone was never wired
        // to it, so a league championship's multiclass setup silently never took
        // effect at registration until now.
        if ($step === 'format') {
            $championship->car_class = $settings['format']['car_class'] ?? null;
            $championship->max_drivers = $settings['format']['max_entries'] ?? null;
            $championship->is_multiclass = (bool) ($settings['format']['multiclass_enabled'] ?? false);
        }

        $championship->save();

        if ($step === 'format') {
            $this->syncChampionshipClasses($championship, $settings['format']['classes'] ?? []);

            if ($request->filled('driver_classes_json')) {
                $this->syncDriverClasses($championship, $this->decodeList($request->input('driver_classes_json'), ['id', 'name', 'acc_category', 'max_entries', 'min_rank', 'max_rank']));
            }
        }
    }

    // Driver classes are matched by id, not name — renaming "Pro" keeps every
    // entry already put in it. A class removed from the list takes only its
    // assignments with it (driver_class_id nulls out), never the registrations.
    private function syncDriverClasses(Championship $championship, array $rows): void
    {
        $existing = $championship->driverClasses()->get()->keyBy('id');
        $keepIds = [];

        foreach ($rows as $i => $row) {
            $name = mb_substr(trim((string) ($row['name'] ?? '')), 0, 50);
            if ($name === '') {
                continue;
            }

            $category = $row['acc_category'] ?? null;
            $maxEntries = $row['max_entries'] ?? null;

            // Rank range, picked From/To in either order — stored lowest-first.
            $rankSlugs = array_column(User::ranks(), 'slug');
            [$minRank, $maxRank] = array_map(
                fn ($rank) => in_array($rank, $rankSlugs, true) ? $rank : null,
                [$row['min_rank'] ?? null, $row['max_rank'] ?? null]
            );
            if ($minRank && $maxRank && array_search($minRank, $rankSlugs, true) < array_search($maxRank, $rankSlugs, true)) {
                [$minRank, $maxRank] = [$maxRank, $minRank];
            }

            $attrs = [
                'name' => $name,
                'acc_category' => is_numeric($category) && array_key_exists((int) $category, ChampionshipDriverClass::ACC_CATEGORIES) ? (int) $category : null,
                'max_entries' => is_numeric($maxEntries) && $maxEntries > 0 ? (int) $maxEntries : null,
                'min_rank' => $minRank,
                'max_rank' => $maxRank,
                'sort_order' => $i,
            ];

            $class = $existing->get((int) ($row['id'] ?? 0));
            if ($class) {
                $class->update($attrs);
            } else {
                $class = $championship->driverClasses()->create($attrs);
            }
            $keepIds[] = $class->id;
        }

        $championship->driverClasses()->whereNotIn('id', $keepIds)->delete();
    }

    // Keeps the real ChampionshipClass rows the public registration flow
    // (ChampionshipController::register(), reused as-is per Phase 4's brief) reads
    // in sync with the wizard's settings.format.classes JSON list. Matched by name
    // rather than replace-all: championship_class_id cascades on delete
    // (2026_06_15_000003_create_championship_registrations_table.php), so blowing
    // away every class on each save would silently unregister every driver in it —
    // only a class actually removed from the list should take its registrations
    // with it.
    private function syncChampionshipClasses(Championship $championship, array $classes): void
    {
        $existing = $championship->classes()->get()->keyBy('name');
        $keepNames = [];

        foreach ($classes as $i => $classData) {
            $name = trim($classData['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $keepNames[] = $name;

            $attrs = [
                // A class picked from the fixed GT2/GT3/GT4/TCX/GTC dropdown (same
                // list the race wizard's own multiclass picker uses) *is* its car
                // class — the name and car_class are the same value. Falls back to
                // a legacy eligible_cars list if a stored row still has one, from
                // before the dropdown replaced free-typed names + a car picker.
                'car_class' => ! empty($classData['eligible_cars']) ? implode(', ', (array) $classData['eligible_cars']) : $name,
                'max_drivers' => $classData['max_entries'] ?? null,
                'sort_order' => $i,
            ];

            if ($existing->has($name)) {
                $existing[$name]->update($attrs);
            } else {
                $championship->classes()->create(array_merge(['name' => $name], $attrs));
            }
        }

        $championship->classes()->whereNotIn('name', $keepNames)->delete();
    }

    // An uploaded file wins, otherwise whatever <x-media-picker> left in
    // {field}_path (a gallery pick, the kept current value, or empty if the
    // picker was explicitly cleared).
    private function resolveMedia(Request $request, string $field, string $folder): ?string
    {
        if ($request->hasFile($field)) {
            $file = $request->file($field);

            return $file->storeAs($folder, Str::uuid().'.'.$file->getClientOriginalExtension(), 'media');
        }

        return $request->filled($field.'_path') ? $request->input($field.'_path') : null;
    }

    private function decodeList(?string $json, array $allowedKeys): array
    {
        $decoded = json_decode($json ?? '[]', true);
        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_map(
            fn ($row) => array_intersect_key((array) $row, array_flip($allowedKeys)),
            $decoded
        ));
    }
}
