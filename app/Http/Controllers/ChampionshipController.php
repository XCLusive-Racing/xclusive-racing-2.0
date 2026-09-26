<?php

namespace App\Http\Controllers;

use App\Models\Championship;
use App\Models\ChampionshipClass;
use App\Models\ChampionshipRegistration;
use App\Models\League;
use App\Models\RacingTeam;
use App\Models\User;
use App\Services\AccCarCatalog;
use App\Services\ChampionshipTeamEntryService;
use App\Services\DiscordRoleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ChampionshipController extends Controller
{
    public const ERR_ALREADY_REGISTERED = 'You are already registered.';

    public const ERR_TEAM_ONLY = 'This is a team championship — your team owner or manager registers the team.';

    // The fixed class list the championship wizard offers (ChampionshipSettingsSchema
    // format.car_class / the classes builder) — a class's car_class is one of these.
    private const CAR_CLASSES = ['GT2', 'GT3', 'GT4', 'TCX', 'GTC'];

    public function index(Request $request)
    {
        if ($slug = $request->query('league')) {
            $league = League::withoutTenantScope()->where('slug', $slug)->firstOrFail();

            // A draft league isn't public yet — hidden from anonymous/ordinary visitors same as
            // an active league's own draft championships already are, but staff (canManage()) and
            // that league's own members can still open it here as a live preview before it goes
            // live, e.g. via the "Preview League" button on the league edit page.
            $user = auth()->user();
            $canPreview = $user && ($user->canManage() || $user->leagueIds()->contains($league->id));
            if ($league->status !== 'active' && ! $canPreview) {
                abort(404);
            }

            $championships = Championship::withoutTenantScope()
                ->withCount(['rounds', 'registrations'])
                ->where('league_id', $league->id)
                ->publiclyVisible()
                ->orderBy('season', 'desc')
                ->orderBy('name')
                ->get();

            return view('championships.index', compact('championships', 'league'));
        }

        $leagues = League::withoutTenantScope()
            ->where('status', 'active')
            ->withCount(['championships' => fn ($q) => $q->withoutTenantScope()->publiclyVisible()])
            ->orderBy('name')
            ->get();

        return view('championships.leagues', compact('leagues'));
    }

    // Championship carries a Tenantable global scope (it's league-owned data), which
    // would otherwise make implicit route-model binding 404 for any visitor who
    // isn't a member of that league — wrong here, this is the public page.
    private function findChampionship(int $id): Championship
    {
        return Championship::withoutTenantScope()->findOrFail($id);
    }

    public function show(int $championship)
    {
        $championship = $this->findChampionship($championship);

        // Real bug found while building Phase 7's branding: League carries the same
        // TenantScope as everything else, so this relation silently resolved to null
        // for anyone who isn't a member of the specific league being viewed — which
        // is every ordinary public visitor. The entire "themed championship page"
        // feature this controller already shipped in Phase 2 never actually worked
        // for the public it was built for; only staff (canManage() bypass) or that
        // league's own members ever saw it render.
        $championship->load([
            'league' => fn ($q) => $q->withoutTenantScope(),
            'classes',
            // Entries still waiting for manual approval aren't entrants yet.
            'registrations' => fn ($q) => $q->approved()->with('user', 'championshipClass'),
        ]);
        $rounds = $championship->rounds()->where('status', '!=', 'draft')->orderBy('round_number')->get();
        $standings = $championship->computeStandings();
        $classStandings = $championship->computeClassStandings();
        $teamStandings = $championship->computeTeamStandings();
        $teamChampionship = $championship->computeTeamChampionship();

        return view('championships.show', compact('championship', 'rounds', 'standings', 'classStandings', 'teamStandings', 'teamChampionship'));
    }

    public function register(Request $request, int $championship)
    {
        $championship = $this->findChampionship($championship);
        $user = $request->user();

        if ($user->isSuspended()) {
            return back()->with('error', 'Your account has been suspended. Please contact an administrator.');
        }

        if (! $championship->acceptsRegistrations()) {
            return back()->with('error', 'Registration is not open.');
        }

        if ($failure = $this->discordMembershipFailure($championship, $user)) {
            return back()->with('error', $failure);
        }

        // Spectators sit in their own pool (settings.format.spectator_slots), not
        // against max_drivers, and skip the driver-only checks below entirely —
        // they aren't racing, so an SR/rating requirement doesn't apply to them.
        if ($request->boolean('is_spectator')) {
            $failure = $this->createUnderLock($championship, function () use ($championship, $user) {
                if ($championship->isRegistered($user)) {
                    return self::ERR_ALREADY_REGISTERED;
                }
                if ($championship->isSpectatorFull()) {
                    return 'There are no spectator slots left.';
                }

                ChampionshipRegistration::create([
                    'championship_id' => $championship->id,
                    'user_id' => $user->id,
                    'is_spectator' => true,
                ]);

                return null;
            });

            return $failure ? back()->with('error', $failure) : back()->with('success', 'You have been registered as a spectator!');
        }

        // Driver-swaps-enabled championships register a team (one row, one of its
        // members' RaceTeamEntry per round handles the actual swap roster) rather
        // than each driver separately — same "owner registers the team" rule
        // RaceController::registerTeam() already uses for individual rounds.
        $team = null;
        $teamEntryFields = [];
        $teamRegistrationScope = $championship->settings->format->team_registration_scope ?? 'per_round';
        if ($championship->settings->format->driver_swaps_enabled ?? false) {
            // A team championship: every driver races as part of a team car, so a
            // solo entry would be a car with nobody to swap with.
            if (! $request->filled('racing_team_id')) {
                return back()->with('error', self::ERR_TEAM_ONLY);
            }

            $team = RacingTeam::with('members')->findOrFail($request->integer('racing_team_id'));
            abort_unless($team->canManage($user), 403);

            // The team picks which of its members drive this car, in both scopes:
            // "championship" enters exactly them into every round, "per_round"
            // pre-selects them on each round's own team sign-up.
            $request->validate([
                'driver_ids' => 'required|array|min:1',
                'driver_ids.*' => 'integer',
            ], ['driver_ids.required' => 'Pick the drivers for your car.']);

            $eligibleIds = $team->members->pluck('id')->push($team->owner_id)->unique();
            $driverIds = collect($request->input('driver_ids'))->map(fn ($id) => (int) $id)->unique()->values();

            if ($driverIds->diff($eligibleIds)->isNotEmpty()) {
                return back()->with('error', 'Every driver must be a member of your team.');
            }
            if ($failure = $championship->driverCountFailure($driverIds->count())) {
                return back()->with('error', $failure);
            }

            $teamEntryFields = ['driver_ids' => $driverIds->all()];

            // "Whole championship" scope also captures the car/starting driver once
            // here and auto-creates the per-round RaceTeamEntry for every existing
            // (and, via ChampionshipWizardController, future) round — see
            // ChampionshipTeamEntryService.
            if ($teamRegistrationScope === 'championship') {
                // ACC cars come from the game's own catalogue (the sign-up form offers
                // them as a dropdown); other games have no list, so free text.
                $carRules = ['nullable', 'string', 'max:255'];
                if (AccCarCatalog::supports($championship->game)) {
                    $carRules[] = Rule::in(array_keys(AccCarCatalog::namesWithClass($championship->game)));
                }

                $validated = $request->validate([
                    'car_number' => 'required|integer|min:0|max:999',
                    'car_model' => $carRules,
                    'starting_driver_id' => 'required|integer',
                ]);

                if (! $driverIds->contains((int) $validated['starting_driver_id'])) {
                    return back()->with('error', 'The starting driver must be one of the selected drivers.');
                }

                $teamEntryFields += [
                    'car_number' => $validated['car_number'],
                    'car_model' => $validated['car_model'] ?? null,
                    'starting_driver_id' => $validated['starting_driver_id'],
                ];
            }
        }

        $thresholds = $championship->requirementThresholds();
        if ($failure = $user->requirementFailure($championship->game, $thresholds['sr'], $thresholds['min'], $thresholds['max'])) {
            return back()->with('error', $failure);
        }

        $classId = null;
        if ($championship->is_multiclass) {
            $request->validate(['championship_class_id' => 'required|exists:championship_classes,id']);
            $classId = $request->integer('championship_class_id');

            $class = ChampionshipClass::where('id', $classId)
                ->where('championship_id', $championship->id)
                ->firstOrFail();

            if ($failure = $user->requirementFailure($championship->game, $class->sr_requirement, $class->min_rating)) {
                return back()->with('error', $failure);
            }
        }

        // The car has to belong to the class the team races in (the multiclass pick,
        // or the championship's single car class) — only checkable for ACC's catalogue.
        $carModel = $teamEntryFields['car_model'] ?? null;
        $requiredClass = isset($class) ? $class->car_class : $championship->car_class;
        $carClass = $carModel ? AccCarCatalog::classOfName($carModel, $championship->game) : null;
        if ($carClass && $requiredClass && in_array($requiredClass, self::CAR_CLASSES, true) && $carClass !== $requiredClass) {
            return back()->withInput()->with('error', "The {$carModel} isn't a {$requiredClass} car.");
        }

        // Every picked driver has to qualify, not just the one registering — the same
        // per-driver check RaceController::registerTeam() does for one round. Without
        // it, e.g. a driver with no Steam ID would land on an ACC PC entrylist with an
        // empty playerID.
        if ($team) {
            $drivers = User::whereIn('id', $teamEntryFields['driver_ids'])->get();

            foreach ($drivers as $driver) {
                $failure = $driver->requirementFailure($championship->game, $thresholds['sr'], $thresholds['min'], $thresholds['max'])
                    ?? (isset($class) ? $driver->requirementFailure($championship->game, $class->sr_requirement, $class->min_rating) : null);

                if ($failure) {
                    return back()->with('error', $driver->displayName().': '.$failure);
                }
            }
        }

        // Manual approval: the entry waits (approved_at null) until the league
        // approves it on the Entries page — only then is a team carried into rounds.
        $pending = $championship->requiresManualApproval();

        $registration = null;
        $failure = $this->createUnderLock($championship, function () use ($championship, $user, $team, $teamEntryFields, $classId, $pending, &$registration) {
            if ($failure = $this->capacityFailure($championship, $user, $team, $teamEntryFields, $classId)) {
                return $failure;
            }

            $registration = ChampionshipRegistration::create(array_merge([
                'championship_id' => $championship->id,
                'user_id' => $user->id,
                'championship_class_id' => $classId,
                'racing_team_id' => $team?->id,
                'approved_at' => $pending ? null : now(),
            ], $teamEntryFields));

            return null;
        });

        if ($failure) {
            return back()->withInput()->with('error', $failure);
        }

        if (! $pending && $team && $teamRegistrationScope === 'championship') {
            app(ChampionshipTeamEntryService::class)->syncAllExistingRounds($registration, $championship);
        }

        $message = match (true) {
            $pending => 'Your entry has been received — the league will review it before it is confirmed.',
            $championship->isRegistrationWaitlisted($user) => 'The championship is full — you have been added to the waiting list.',
            (bool) $team => 'Your team has been registered for the championship!',
            default => 'You have been registered for the championship!',
        };

        return back()->with('success', $message);
    }

    // Runs $create inside a transaction holding a row lock on the championship, so
    // two sign-ups at the same instant can't both pass a capacity check (last spot,
    // team car limit, car number, a driver in two cars) before either insert lands —
    // same fix as RaceController::register(). $create returns an error or null.
    private function createUnderLock(Championship $championship, callable $create): ?string
    {
        return DB::transaction(function () use ($championship, $create) {
            Championship::withoutTenantScope()->whereKey($championship->id)->lockForUpdate()->first();

            return $create();
        });
    }

    // Every check that depends on who else has registered — only authoritative
    // inside createUnderLock().
    private function capacityFailure(Championship $championship, User $user, ?RacingTeam $team, array $teamEntryFields, ?int $classId): ?string
    {
        if ($team) {
            // Each car is its own registration, up to settings.format.max_cars_per_team.
            $maxCars = $championship->maxCarsPerTeam();
            if ($championship->teamCarRegistrations($team)->count() >= $maxCars) {
                return $maxCars === 1
                    ? 'Your team is already registered for this championship.'
                    : "Your team already has the maximum of {$maxCars} cars in this championship.";
            }

            $alreadyInACar = collect($teamEntryFields['driver_ids'])->intersect($championship->driverIdsInCars());
            if ($alreadyInACar->isNotEmpty()) {
                $names = User::whereIn('id', $alreadyInACar)->get()->map->displayName()->join(', ');

                return "{$names} already drives another car in this championship — a driver can only be in one car.";
            }

            // The number follows the car into every round, where it has to be unique.
            $carNumber = $teamEntryFields['car_number'] ?? null;
            if ($carNumber !== null && $championship->registrations()->where('car_number', $carNumber)->exists()) {
                return 'Car number #'.$carNumber.' is already taken in this championship.';
            }
        } elseif ($championship->isRegistered($user)) {
            // A team entering another car is "already registered" by definition —
            // the per-team car limit above covers that case instead.
            return self::ERR_ALREADY_REGISTERED;
        }

        if ($championship->isFull() && ! $championship->waitlistEnabled()) {
            return 'Championship is full.';
        }

        if ($classId !== null && ChampionshipClass::find($classId)?->isFull()) {
            return 'The selected class is full.';
        }

        return null;
    }

    // Phase 4 (docs/championships/PLAN.md): requires_discord_membership was purely
    // cosmetic (a warning string on the public page) until now. Returns null when
    // registration may proceed, or a user-facing error message when it may not.
    // A positive membership result is cached briefly per user+guild so repeated
    // registration attempts (e.g. retrying after fixing something else) don't
    // hit Discord's API every time; a negative/unknown result is never cached,
    // so someone who just joined the server isn't stuck behind a stale "no."
    private function discordMembershipFailure(Championship $championship, User $user): ?string
    {
        // Bypass the tenant scope deliberately — a driver checking whether they can
        // register for a public championship isn't a member of that league (that's
        // the whole point), so the plain $championship->league relation would
        // silently resolve to null for them and skip this check entirely.
        $league = $championship->league()->withoutTenantScope()->first();

        // Two independent opt-ins exist — League.requires_discord_membership (a
        // league-wide default, Phase 1) and settings.requirements.discord_membership_required
        // (per-championship, Phase 2's schema) — and Phase 4 originally only checked
        // the first. Found while confirming this gate for Phase 7: either one
        // requiring it is enough, so a championship-level opt-in on a league that
        // doesn't require it league-wide still gets enforced.
        $required = ($league && $league->requires_discord_membership)
            || ($championship->settings->requirements->discord_membership_required ?? false);

        if (! $league || ! $required) {
            return null;
        }

        if (! $league->discord_guild_id) {
            // League opted in but nobody configured the guild — an XCL/league setup
            // gap, not something to block a driver's registration over.
            Log::warning('League requires Discord membership but has no discord_guild_id set', ['league_id' => $league->id]);

            return null;
        }

        $discord = $user->connectedAccount('discord');
        if (! $discord) {
            return 'Connect your Discord account on your profile before registering — '.$league->name.' requires Discord membership.';
        }

        $cacheKey = "discord-membership:{$league->discord_guild_id}:{$discord->provider_id}";

        if (Cache::get($cacheKey)) {
            return null;
        }

        $isMember = app(DiscordRoleService::class)->isGuildMember($league->discord_guild_id, $discord->provider_id);

        if ($isMember === true) {
            Cache::put($cacheKey, true, now()->addMinutes(10));

            return null;
        }

        if ($isMember === false) {
            return 'You must join '.$league->name."'s Discord server before registering."
                .($league->discord_invite_url ? ' Join here: '.$league->discord_invite_url : '');
        }

        return "Couldn't verify your Discord membership right now — please try again in a moment.";
    }

    public function unregister(int $championship)
    {
        $championship = $this->findChampionship($championship);
        $user = request()->user();

        // A team's registration row belongs to whoever submitted it (the owner, at the
        // time) -- a manager unregistering the team has no row of their own to match
        // on user_id, so this also matches by their manageable team's racing_team_id.
        $team = $user->manageableRacingTeam();

        $registrations = $championship->registrations()
            ->where(function ($query) use ($user, $team) {
                $query->where('user_id', $user->id);
                if ($team) {
                    $query->orWhere('racing_team_id', $team->id);
                }
            });

        // A team with several cars withdraws one car at a time.
        if (request()->filled('registration_id')) {
            $registrations->whereKey(request()->integer('registration_id'));
        }

        $registrations->delete();

        return back()->with('success', request()->filled('registration_id')
            ? 'The car has been withdrawn from the championship.'
            : 'You have been unregistered from the championship.');
    }
}
