<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Championship\ApproveChampionshipRatingRequest;
use App\Http\Requests\Championship\PublishChampionshipRequest;
use App\Http\Requests\Championship\SaveChampionshipStepRequest;
use App\Jobs\PushRoundConfigJob;
use App\Models\Championship;
use App\Models\FtpServer;
use App\Models\League;
use App\Models\ChampionshipRegistration;
use App\Models\PointsScheme;
use App\Models\Race;
use App\Rules\PracticeWindowNotOverlapping;
use App\Services\AuditLogger;
use App\Services\ChampionshipTeamEntryService;
use App\Services\PracticeServer\PracticeServerSessionManager;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Http\Request;
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

        $user    = $request->user();
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
            'league_id'  => $league->id,
            'name'       => 'New Championship',
            'game'       => 'acc',
            'season'     => now()->year,
            'status'     => 'draft',
            'visibility' => 'public',
            'settings'   => ChampionshipSettingsSchema::defaults(),
        ]);

        AuditLogger::record($request->user(), $championship, 'championship.created', null, $league->id);

        return redirect()->route('admin.leagues.championships.wizard', [$league, $championship, 'basics']);
    }

    public function edit(Request $request, League $league, Championship $championship, string $step)
    {
        $this->assertLeagueOfInterest($request, $league, $championship);
        Gate::authorize('view', $championship);
        abort_unless(array_key_exists($step, ChampionshipSettingsSchema::STEPS), 404);

        $pointsSchemes = PointsScheme::orderByDesc('is_template')->orderBy('name')->get();

        // Only needed by the Basics step's Server dropdown, but cheap enough to
        // always pass — scoped to this league's own active servers, same as
        // roundCreate() below.
        $servers = $league->ftpServers()->where('active', true)->orderBy('name')->get();

        return view('admin.leagues.championships.wizard', [
            'league'        => $league,
            'championship'  => $championship,
            'step'          => $step,
            'steps'         => ChampionshipSettingsSchema::STEPS,
            'fields'        => ChampionshipSettingsSchema::fieldsForStep($step),
            'pointsSchemes' => $pointsSchemes,
            'servers'       => $servers,
            'canApproveRating' => $request->user()->can('approveRating', $championship),
        ]);
    }

    public function update(SaveChampionshipStepRequest $request, League $league, Championship $championship, string $step)
    {
        $this->assertLeagueOfInterest($request, $league, $championship);
        abort_unless(array_key_exists($step, ChampionshipSettingsSchema::STEPS) && $step !== 'review', 404);

        $before = $step === 'basics'
            ? $championship->only(['name', 'slug', 'game', 'platform', 'visibility'])
            : $championship->settings->toArray();

        if ($step === 'basics') {
            $data = $request->validated();
            $data['image'] = $this->resolveMedia($request);
            unset($data['image_path']);

            // Nested settings.schedule.* comes along in validated() too (it rides
            // on this same step) — applyStepSettings() below is what actually
            // merges that into the settings blob; passing the raw partial array
            // straight to update() would blow away every other settings group.
            unset($data['settings']);

            if (!empty($data['ftp_server_id']) && !$league->ftpServers()->where('id', $data['ftp_server_id'])->exists()) {
                abort(403, 'That server does not belong to this league.');
            }

            $championship->update($data);
            $this->applyStepSettings($request, $championship, 'basics');
        } else {
            $this->applyStepSettings($request, $championship, $step);
        }

        AuditLogger::record($request->user(), $championship, 'championship.step_saved', ['step' => $step, 'before' => $before]);

        $next = $this->nextStep($step);

        return redirect()->route('admin.leagues.championships.wizard', [$league, $championship, $next])
            ->with('success', ucfirst($step) . ' saved.');
    }

    public function roundCreate(Request $request, League $league, Championship $championship)
    {
        $this->assertLeagueOfInterest($request, $league, $championship);
        Gate::authorize('update', $championship);

        // Scoped to this league's own assigned servers only — a league manager
        // must never be able to push a round's config to another league's server.
        $servers = $league->ftpServers()->where('active', true)->orderBy('name')->get();

        $nextRoundNumber      = $championship->rounds()->max('round_number') + 1;
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
            'league', 'championship', 'servers', 'suggestedScheduledAt', 'nextRoundNumber', 'bulkSuggestions'
        ));
    }

    public function addRound(Request $request, League $league, Championship $championship)
    {
        $this->assertLeagueOfInterest($request, $league, $championship);
        Gate::authorize('update', $championship);

        $data = $request->validate([
            'track'               => 'required|string|max:255',
            'scheduled_at'        => array_filter([
                'required', 'date',
                $request->boolean('has_practice_server') ? new PracticeWindowNotOverlapping() : null,
            ]),
            'round_number'        => 'nullable|integer|min:1',
            'practice_duration'   => 'nullable|integer|min:1|max:999',
            'qualifying_duration' => 'nullable|integer|min:1|max:999',
            'race_duration'       => 'nullable|integer|min:1|max:999',
            'weather'             => 'nullable|in:dry,wet,mixed,random',
            'weather_randomness'  => 'nullable|in:0,1,2,3,4,5,6,7,random',
            'rain_level'          => 'nullable|numeric|min:0|max:1',
            'time_of_day'         => 'nullable|date_format:H:i',
            'ambient_temp'        => 'nullable|integer|min:-30|max:50',
            'description'         => 'nullable|string',
            'xcl_r_multiplier'    => 'nullable|numeric|min:0.1|max:10',
            'pitstop_count'       => 'nullable|integer|min:0|max:9',
            'min_stop_secs'       => 'nullable|integer|min:1|max:3600',
            'driver_stint_time_mins'      => 'nullable|integer|min:1|max:1440',
            'max_total_driving_time_mins' => 'nullable|integer|min:1|max:1440',
            'mandatory_driver_swap'       => 'nullable|boolean',
            'has_practice_server'         => 'nullable|boolean',
            'practice_notes'              => 'nullable|string|max:2000',
            // Restricted to this league's own servers — never any $id a manager
            // could otherwise guess, which is why this isn't just "exists:ftp_servers,id".
            'ftp_server_id'       => 'nullable|exists:ftp_servers,id',
        ]);

        $data['mandatory_driver_swap'] = $request->boolean('mandatory_driver_swap');
        $data['has_practice_server']   = $request->boolean('has_practice_server');

        $claimedSlots = [];
        $result = $this->resolveRoundRow($data, $championship, $league, $claimedSlots);

        if (is_string($result)) {
            return back()->withInput()->withErrors(['scheduled_at' => $result]);
        }

        $race = Race::create($result);
        $this->syncTeamEntriesForNewRound($championship, $race);

        $practiceWarning = (new PracticeServerSessionManager())->sync($race, $result['has_practice_server']);

        AuditLogger::record($request->user(), $championship, 'championship.round_added', ['title' => $result['title']]);

        $redirect = redirect()->route('admin.leagues.championships.wizard', [$league, $championship, 'rounds'])
            ->with('success', 'Round added.');

        return $practiceWarning ? $redirect->with('practice_warning', $practiceWarning) : $redirect;
    }

    public function roundEdit(Request $request, League $league, Championship $championship, Race $race)
    {
        $this->assertLeagueOfInterest($request, $league, $championship);
        Gate::authorize('update', $championship);
        abort_unless($race->championship_id === $championship->id, 404);

        $servers = $league->ftpServers()->where('active', true)->orderBy('name')->get();

        return view('admin.leagues.championships.round-edit', compact('league', 'championship', 'race', 'servers'));
    }

    // Same validity rules Add Round enforces (resolveRoundRow()), except the
    // slot-collision check excludes this round's own current slot — otherwise
    // editing a round without changing its time would reject against itself.
    public function updateRound(Request $request, League $league, Championship $championship, Race $race)
    {
        $this->assertLeagueOfInterest($request, $league, $championship);
        Gate::authorize('update', $championship);
        abort_unless($race->championship_id === $championship->id, 404);

        $data = $request->validate([
            'track'               => 'required|string|max:255',
            'scheduled_at'        => array_filter([
                'required', 'date',
                $request->boolean('has_practice_server') ? new PracticeWindowNotOverlapping($race->id) : null,
            ]),
            'round_number'        => 'nullable|integer|min:1',
            'practice_duration'   => 'nullable|integer|min:1|max:999',
            'qualifying_duration' => 'nullable|integer|min:1|max:999',
            'race_duration'       => 'nullable|integer|min:1|max:999',
            'weather'             => 'nullable|in:dry,wet,mixed,random',
            'weather_randomness'  => 'nullable|in:0,1,2,3,4,5,6,7,random',
            'rain_level'          => 'nullable|numeric|min:0|max:1',
            'time_of_day'         => 'nullable|date_format:H:i',
            'ambient_temp'        => 'nullable|integer|min:-30|max:50',
            'description'         => 'nullable|string',
            'xcl_r_multiplier'    => 'nullable|numeric|min:0.1|max:10',
            'pitstop_count'       => 'nullable|integer|min:0|max:9',
            'min_stop_secs'       => 'nullable|integer|min:1|max:3600',
            'driver_stint_time_mins'      => 'nullable|integer|min:1|max:1440',
            'max_total_driving_time_mins' => 'nullable|integer|min:1|max:1440',
            'mandatory_driver_swap'       => 'nullable|boolean',
            'has_practice_server'         => 'nullable|boolean',
            'practice_notes'              => 'nullable|string|max:2000',
            'ftp_server_id'       => 'nullable|exists:ftp_servers,id',
        ]);

        $data['mandatory_driver_swap'] = $request->boolean('mandatory_driver_swap');
        $data['has_practice_server']   = $request->boolean('has_practice_server');

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

        $practiceWarning = (new PracticeServerSessionManager())->sync($race, $result['has_practice_server']);

        AuditLogger::record($request->user(), $championship, 'championship.round_updated', ['race_id' => $race->id]);

        $redirect = redirect()->route('admin.leagues.championships.wizard', [$league, $championship, 'rounds'])
            ->with('success', 'Round updated.');

        return $practiceWarning ? $redirect->with('practice_warning', $practiceWarning) : $redirect;
    }

    // "Bulk" the same way admin/races/bulk-create.blade.php is: generate a run of
    // rounds from one shared set of session/weather/server settings, each only
    // needing its own track and date — reusing resolveRoundRow() per row so the
    // exact same slot/validity rules single-round Add Round already enforces
    // apply here too. All-or-nothing: one bad row rejects the whole batch rather
    // than creating half of it, same convention bulkStore() uses for races.
    public function bulkAddRounds(Request $request, League $league, Championship $championship)
    {
        $this->assertLeagueOfInterest($request, $league, $championship);
        Gate::authorize('update', $championship);

        $data = $request->validate([
            'practice_duration'      => 'nullable|integer|min:1|max:999',
            'qualifying_duration'    => 'nullable|integer|min:1|max:999',
            'race_duration'          => 'nullable|integer|min:1|max:999',
            'weather'                => 'nullable|in:dry,wet,mixed,random',
            'weather_randomness'     => 'nullable|in:0,1,2,3,4,5,6,7,random',
            'rain_level'             => 'nullable|numeric|min:0|max:1',
            'time_of_day'            => 'nullable|date_format:H:i',
            'ambient_temp'           => 'nullable|integer|min:-30|max:50',
            'description'            => 'nullable|string',
            'xcl_r_multiplier'       => 'nullable|numeric|min:0.1|max:10',
            'pitstop_count'          => 'nullable|integer|min:0|max:9',
            'min_stop_secs'          => 'nullable|integer|min:1|max:3600',
            'driver_stint_time_mins'      => 'nullable|integer|min:1|max:1440',
            'max_total_driving_time_mins' => 'nullable|integer|min:1|max:1440',
            'mandatory_driver_swap'       => 'nullable|boolean',
            // Not validated against overlapping practice-server windows here (unlike
            // Add/Edit Round's PracticeWindowNotOverlapping) — one has_practice_server
            // flag shared across a whole generated batch of dates makes a per-row
            // overlap check meaningful only per row, not worth the added complexity
            // for what's expected to be a rare combination; PracticeServerSessionManager
            // itself still owns each individual sync below.
            'has_practice_server'         => 'nullable|boolean',
            'practice_notes'              => 'nullable|string|max:2000',
            'ftp_server_id'          => 'nullable|exists:ftp_servers,id',
            'rounds'                 => 'required|array|min:1',
            'rounds.*.track'         => 'required|string|max:255',
            'rounds.*.scheduled_at'  => 'required|date',
            'rounds.*.round_number'  => 'nullable|integer|min:1',
        ]);

        $data['mandatory_driver_swap'] = $request->boolean('mandatory_driver_swap');
        $data['has_practice_server']   = $request->boolean('has_practice_server');

        $shared = collect($data)->except('rounds')->all();
        $rows   = [];
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
                return back()->withInput()->withErrors(['rounds' => 'Row ' . ($i + 1) . ' (' . ($row['track'] ?: '—') . '): ' . $result]);
            }

            $rows[] = $result;
        }

        DB::transaction(function () use ($rows, $championship) {
            $practiceManager = new PracticeServerSessionManager();
            foreach ($rows as $row) {
                $race = Race::create($row);
                $this->syncTeamEntriesForNewRound($championship, $race);
                $practiceManager->sync($race, $row['has_practice_server']);
            }
        });

        AuditLogger::record($request->user(), $championship, 'championship.rounds_bulk_added', ['count' => count($rows)]);

        return redirect()->route('admin.leagues.championships.wizard', [$league, $championship, 'rounds'])
            ->with('success', count($rows) . ' rounds added.');
    }

    // "Championship"-scope team registrations (settings.format.team_registration_scope,
    // see ChampionshipTeamEntryService) carried a car number/model/starting driver
    // once instead of re-registering every round — a round added after the fact
    // still needs its own RaceTeamEntry generated from that, which is what this does.
    private function syncTeamEntriesForNewRound(Championship $championship, Race $race): void
    {
        $registrations = ChampionshipRegistration::where('championship_id', $championship->id)
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
        if (!empty($data['ftp_server_id']) && !$league->ftpServers()->where('id', $data['ftp_server_id'])->exists()) {
            abort(403, 'That server does not belong to this league.');
        }

        // Rain level is only meaningful for wet/mixed weather — drop a stray value
        // otherwise, same reasoning as RaceController::normalizeRainLevel().
        if (!in_array($data['weather'] ?? null, ['wet', 'mixed'], true)) {
            $data['rain_level'] = null;
        }

        $data['championship_id'] = $championship->id;
        $data['game']            = $championship->game;
        $data['car_class']       = $championship->car_class;
        $data['max_drivers']     = $championship->max_drivers;
        $data['status']          = 'open';
        $data['is_championship'] = true;
        $data['event_tag']       = 'championship';
        $data['scheduled_at']    = \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $data['scheduled_at'], 'Europe/London')->utc();

        // Rounds start on the hour only — the datetime picker already restricts
        // this client-side, but a raw request could still smuggle in a half hour.
        if ($data['scheduled_at']->minute !== 0) {
            return 'Rounds can only start on the hour.';
        }

        if (empty($data['round_number'])) {
            $data['round_number'] = $championship->rounds()->max('round_number') + 1;
        }

        // "Title" is chosen once at Basics (the championship's Name) and composed
        // with the round number here — a manager never re-types it per round.
        $data['title'] = $championship->name . ' — Round ' . $data['round_number'];

        if (!empty($data['ftp_server_id'])) {
            $server   = FtpServer::find($data['ftp_server_id']);
            $slotKey  = $data['scheduled_at']->format('Y-m-d H:i');

            if ($server && !$server->isValidSlot($data['scheduled_at'])) {
                return 'That time is not a valid slot on this server.';
            }
            if ($server && (in_array($slotKey, $server->takenSlots($excludeRaceId), true) || in_array($slotKey, $claimedSlots, true))) {
                return 'That slot is already taken on this server.';
            }

            $claimedSlots[] = $slotKey;
            $data['slot_time']          = $data['scheduled_at']->copy();
            $data['config_push_status'] = 'pending';
        } else {
            $data['slot_time'] = null;
        }

        return $data;
    }

    public function removeRound(Request $request, League $league, Championship $championship, Race $race)
    {
        $this->assertLeagueOfInterest($request, $league, $championship);
        Gate::authorize('update', $championship);
        abort_unless($race->championship_id === $championship->id, 404);

        $race->update(['championship_id' => null, 'round_number' => null]);

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
        $this->assertLeagueOfInterest($request, $league, $championship);
        Gate::authorize('update', $championship);
        abort_unless($race->championship_id === $championship->id, 404);
        abort_unless($race->ftp_server_id, 404);

        Race::where('id', $race->id)->update(['config_push_status' => 'pending']);

        PushRoundConfigJob::dispatch($race->id);

        AuditLogger::record($request->user(), $championship, 'championship.round_config_push_queued', ['race_id' => $race->id]);

        return back()->with('success', 'Config push queued for ' . $race->title . '.');
    }

    public function publish(PublishChampionshipRequest $request, League $league, Championship $championship)
    {
        $this->assertLeagueOfInterest($request, $league, $championship);

        $championship->update(['status' => 'published']);

        AuditLogger::record($request->user(), $championship, 'championship.published');

        return redirect()->route('admin.leagues.championships.wizard', [$league, $championship, 'review'])
            ->with('success', $championship->name . ' has been published.');
    }

    public function openRegistration(Request $request, League $league, Championship $championship)
    {
        $this->assertLeagueOfInterest($request, $league, $championship);
        Gate::authorize('update', $championship);
        abort_unless(in_array($championship->status, ['published', 'registration_closed'], true), 404);

        $championship->update(['status' => 'registration_open', 'registration_open' => true]);

        AuditLogger::record($request->user(), $championship, 'championship.registration_opened');

        return back()->with('success', 'Registration is now open for ' . $championship->name . '.');
    }

    public function closeRegistration(Request $request, League $league, Championship $championship)
    {
        $this->assertLeagueOfInterest($request, $league, $championship);
        Gate::authorize('update', $championship);
        abort_unless($championship->status === 'registration_open', 404);

        $championship->update(['status' => 'registration_closed', 'registration_open' => false]);

        AuditLogger::record($request->user(), $championship, 'championship.registration_closed');

        return back()->with('success', 'Registration is now closed for ' . $championship->name . '.');
    }

    public function requestRating(Request $request, League $league, Championship $championship)
    {
        $this->assertLeagueOfInterest($request, $league, $championship);
        Gate::authorize('requestRating', $championship);

        $championship->requestXclRating();

        AuditLogger::record($request->user(), $championship, 'championship.rating_requested');

        return back()->with('success', 'XCL Rating requested. An XCL admin will review it.');
    }

    // The only route that can ever turn xcl_rating_enabled on. Authorization is
    // enforced by ApproveChampionshipRatingRequest itself (policy-gated), not
    // just by this controller — a league manager can never reach this action.
    public function approveRating(ApproveChampionshipRatingRequest $request, League $league, Championship $championship)
    {
        $this->assertLeagueOfInterest($request, $league, $championship);

        $championship->approveXclRating($request->user());

        AuditLogger::record($request->user(), $championship, 'championship.rating_approved', [
            'approved_by' => $request->user()->id,
        ]);

        return back()->with('success', 'XCL Rating approved for ' . $championship->name . '.');
    }

    public function revokeRating(Request $request, League $league, Championship $championship)
    {
        $this->assertLeagueOfInterest($request, $league, $championship);
        abort_unless($request->user()->canManage(), 403);

        $championship->revokeXclRating();

        AuditLogger::record($request->user(), $championship, 'championship.rating_revoked');

        return back()->with('success', 'XCL Rating revoked for ' . $championship->name . '.');
    }

    // A championship's URL is nested under a league, but a canManage() user
    // bypasses the tenant scope entirely and could otherwise reach this action
    // with mismatched {league}/{championship} ids — guard the pairing directly.
    private function assertLeagueOfInterest(Request $request, League $league, ?Championship $championship = null): void
    {
        if ($championship) {
            abort_unless($championship->league_id === $league->id, 404);
        }
    }

    private function nextStep(string $step): string
    {
        $order = array_keys(ChampionshipSettingsSchema::STEPS);
        $index = array_search($step, $order, true);

        return $order[$index + 1] ?? 'review';
    }

    private function applyStepSettings(Request $request, Championship $championship, string $step): void
    {
        $groups   = ChampionshipSettingsSchema::STEP_GROUPS[$step] ?? [];
        $settings = $championship->settings->toArray();
        $input    = $request->input('settings', []);

        foreach ($groups as $group) {
            $settings[$group] = array_merge($settings[$group] ?? [], $input[$group] ?? []);

            foreach (ChampionshipSettingsSchema::fieldsForGroup($group) as $field) {
                if ($field['type'] === 'boolean') {
                    $settings[$group][$field['key']] = $request->boolean("settings.{$group}.{$field['key']}");
                }
            }
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
            $championship->car_class    = $settings['format']['car_class'] ?? null;
            $championship->max_drivers  = $settings['format']['max_entries'] ?? null;
            $championship->is_multiclass = (bool) ($settings['format']['multiclass_enabled'] ?? false);
        }

        $championship->save();

        if ($step === 'format') {
            $this->syncChampionshipClasses($championship, $settings['format']['classes'] ?? []);
        }
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
                'car_class'   => !empty($classData['eligible_cars']) ? implode(', ', (array) $classData['eligible_cars']) : null,
                'max_drivers' => $classData['max_entries'] ?? null,
                'sort_order'  => $i,
            ];

            if ($existing->has($name)) {
                $existing[$name]->update($attrs);
            } else {
                $championship->classes()->create(array_merge(['name' => $name], $attrs));
            }
        }

        $championship->classes()->whereNotIn('name', $keepNames)->delete();
    }

    // Same resolveMedia() pattern as the legacy native-championship form
    // (Admin\ChampionshipController) — an uploaded file wins, otherwise use
    // whatever <x-media-picker> left in image_path (a gallery pick, the kept
    // current value, or empty if the picker was explicitly cleared).
    private function resolveMedia(Request $request): ?string
    {
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            return $file->storeAs('images/championships', Str::uuid() . '.' . $file->getClientOriginalExtension(), 'media');
        }

        return $request->filled('image_path') ? $request->image_path : null;
    }

    private function decodeList(?string $json, array $allowedKeys): array
    {
        $decoded = json_decode($json ?? '[]', true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_map(
            fn ($row) => array_intersect_key((array) $row, array_flip($allowedKeys)),
            $decoded
        ));
    }
}
