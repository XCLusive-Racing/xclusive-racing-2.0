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
use App\Models\PointsScheme;
use App\Models\Race;
use App\Services\AuditLogger;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Http\Request;
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

            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->storeAs('images/championships', Str::uuid() . '.' . $request->file('image')->getClientOriginalExtension(), 'media');
            } elseif ($request->boolean('image_remove')) {
                $data['image'] = null;
            } else {
                unset($data['image']);
            }

            // Nested settings.{schedule,sessions}.* come along in validated() too
            // (they ride on this same step) — applyStepSettings() below is what
            // actually merges those into the settings blob; passing the raw
            // partial array straight to update() would blow away every other
            // settings group instead of just schedule/sessions.
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

        return view('admin.leagues.championships.round-create', compact('league', 'championship', 'servers', 'suggestedScheduledAt'));
    }

    public function addRound(Request $request, League $league, Championship $championship)
    {
        $this->assertLeagueOfInterest($request, $league, $championship);
        Gate::authorize('update', $championship);

        $data = $request->validate([
            'track'               => 'required|string|max:255',
            'scheduled_at'        => 'required|date',
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
            // Restricted to this league's own servers — never any $id a manager
            // could otherwise guess, which is why this isn't just "exists:ftp_servers,id".
            'ftp_server_id'       => 'nullable|exists:ftp_servers,id',
        ]);

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
            return back()->withInput()->withErrors(['scheduled_at' => 'Rounds can only start on the hour.']);
        }

        if (empty($data['round_number'])) {
            $data['round_number'] = $championship->rounds()->max('round_number') + 1;
        }

        // "Title" is chosen once at Basics (the championship's Name) and composed
        // with the round number here — a manager never re-types it per round.
        $data['title'] = $championship->name . ' — Round ' . $data['round_number'];

        if (!empty($data['ftp_server_id'])) {
            $server = FtpServer::find($data['ftp_server_id']);

            if ($server && !$server->isValidSlot($data['scheduled_at'])) {
                return back()->withInput()->withErrors(['scheduled_at' => 'That time is not a valid slot on this server.']);
            }
            if ($server && in_array($data['scheduled_at']->format('Y-m-d H:i'), $server->takenSlots(), true)) {
                return back()->withInput()->withErrors(['scheduled_at' => 'That slot is already taken on this server.']);
            }

            $data['slot_time']          = $data['scheduled_at']->copy();
            $data['config_push_status'] = 'pending';
        } else {
            $data['slot_time'] = null;
        }

        Race::create($data);

        AuditLogger::record($request->user(), $championship, 'championship.round_added', ['title' => $data['title']]);

        return redirect()->route('admin.leagues.championships.wizard', [$league, $championship, 'rounds'])
            ->with('success', 'Round added.');
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
