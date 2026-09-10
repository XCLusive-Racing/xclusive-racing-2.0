<?php

namespace App\Http\Controllers;

use App\Models\Championship;
use App\Models\ChampionshipClass;
use App\Models\ChampionshipRegistration;
use App\Models\League;
use App\Models\RacingTeam;
use App\Models\User;
use App\Services\DiscordRoleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ChampionshipController extends Controller
{
    // A league championship's own lifecycle (set by the setup wizard) is
    // draft/published/registration_open/registration_closed/running/completed/
    // cancelled — distinct from the flat XCL championship's draft/active/finished.
    // Anything from "published" onward is publicly visible; draft and cancelled are not.
    private const LEAGUE_PUBLIC_STATUSES = ['published', 'registration_open', 'registration_closed', 'running', 'completed'];

    public function index(Request $request)
    {
        if ($slug = $request->query('league')) {
            $league = League::withoutTenantScope()->where('slug', $slug)->where('status', 'active')->firstOrFail();

            $championships = Championship::withoutTenantScope()
                ->withCount(['rounds', 'registrations'])
                ->where('league_id', $league->id)
                ->whereIn('status', self::LEAGUE_PUBLIC_STATUSES)
                ->orderBy('season', 'desc')
                ->orderBy('name')
                ->get();

            return view('championships.index', compact('championships', 'league'));
        }

        $leagues = League::withoutTenantScope()
            ->where('status', 'active')
            ->withCount(['championships' => fn ($q) => $q->withoutTenantScope()->whereIn('status', self::LEAGUE_PUBLIC_STATUSES)])
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
            'classes', 'registrations.user', 'registrations.championshipClass',
        ]);
        $rounds         = $championship->rounds()->where('status', '!=', 'draft')->orderBy('round_number')->get();
        $standings      = $championship->computeStandings();
        $classStandings = $championship->computeClassStandings();
        $teamStandings  = $championship->computeTeamStandings();

        // Native championships (league_id = XCL's own system league, Phase 2.5) don't
        // use the settings schema for requirements/penalties at all — they'd show as
        // all-defaults here, which would misrepresent them. Only show the
        // settings-driven Requirements/Rules/Prizes/Penalties sections (Phase 7) for
        // an actual league-owned championship.
        $isLeagueOwned = $championship->league && $championship->league->id !== League::system()->id;

        return view('championships.show', compact('championship', 'rounds', 'standings', 'classStandings', 'teamStandings', 'isLeagueOwned'));
    }

    public function register(Request $request, int $championship)
    {
        $championship = $this->findChampionship($championship);
        $user = $request->user();

        if ($user->isSuspended()) {
            return back()->with('error', 'Your account has been suspended. Please contact an administrator.');
        }

        if (!in_array($championship->status, ['active', 'registration_open'], true) || !$championship->registration_open) {
            return back()->with('error', 'Registration is not open.');
        }

        if (!$championship->registrationIsOpen()) {
            return back()->with('error', 'Registration is not open right now.');
        }

        if ($championship->isRegistered($user)) {
            return back()->with('error', 'You are already registered.');
        }

        if ($failure = $this->discordMembershipFailure($championship, $user)) {
            return back()->with('error', $failure);
        }

        // Spectators sit in their own pool (settings.format.spectator_slots), not
        // against max_drivers, and skip the driver-only checks below entirely —
        // they aren't racing, so an SR/rating requirement doesn't apply to them.
        if ($request->boolean('is_spectator')) {
            if ($championship->isSpectatorFull()) {
                return back()->with('error', 'There are no spectator slots left.');
            }

            ChampionshipRegistration::create([
                'championship_id' => $championship->id,
                'user_id'         => $user->id,
                'is_spectator'    => true,
            ]);

            return back()->with('success', 'You have been registered as a spectator!');
        }

        // Driver-swaps-enabled championships register a team (one row, one of its
        // members' RaceTeamEntry per round handles the actual swap roster) rather
        // than each driver separately — same "owner registers the team" rule
        // RaceController::registerTeam() already uses for individual rounds.
        $team = null;
        if ($championship->settings->format->driver_swaps_enabled ?? false) {
            if ($request->filled('racing_team_id')) {
                $team = RacingTeam::where('id', $request->integer('racing_team_id'))
                    ->where('owner_id', $user->id)
                    ->firstOrFail();

                if ($championship->registrations()->where('racing_team_id', $team->id)->exists()) {
                    return back()->with('error', 'Your team is already registered for this championship.');
                }
            }
        }

        if ($championship->isFull() && !$championship->waitlistEnabled()) {
            return back()->with('error', 'Championship is full.');
        }

        $thresholds = $championship->requirementThresholds();
        if ($failure = $user->requirementFailure($championship->game, $thresholds['sr'], $thresholds['min'])) {
            return back()->with('error', $failure);
        }

        $classId = null;
        if ($championship->is_multiclass) {
            $request->validate(['championship_class_id' => 'required|exists:championship_classes,id']);
            $classId = $request->championship_class_id;

            $class = ChampionshipClass::where('id', $classId)
                ->where('championship_id', $championship->id)
                ->firstOrFail();

            if ($class->isFull()) {
                return back()->with('error', 'The selected class is full.');
            }

            if ($failure = $user->requirementFailure($championship->game, $class->sr_requirement, $class->min_rating)) {
                return back()->with('error', $failure);
            }
        }

        ChampionshipRegistration::create([
            'championship_id'       => $championship->id,
            'user_id'               => $user->id,
            'championship_class_id' => $classId,
            'racing_team_id'        => $team?->id,
        ]);

        $message = $championship->isRegistrationWaitlisted($user)
            ? 'The championship is full — you have been added to the waiting list.'
            : ($team ? 'Your team has been registered for the championship!' : 'You have been registered for the championship!');

        return back()->with('success', $message);
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

        if (!$league || !$required) {
            return null;
        }

        if (!$league->discord_guild_id) {
            // League opted in but nobody configured the guild — an XCL/league setup
            // gap, not something to block a driver's registration over.
            Log::warning('League requires Discord membership but has no discord_guild_id set', ['league_id' => $league->id]);
            return null;
        }

        $discord = $user->connectedAccount('discord');
        if (!$discord) {
            return 'Connect your Discord account on your profile before registering — ' . $league->name . ' requires Discord membership.';
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
            return 'You must join ' . $league->name . "'s Discord server before registering."
                . ($league->discord_invite_url ? ' Join here: ' . $league->discord_invite_url : '');
        }

        return "Couldn't verify your Discord membership right now — please try again in a moment.";
    }

    public function unregister(int $championship)
    {
        $championship = $this->findChampionship($championship);
        $user = request()->user();

        $championship->registrations()
            ->where('user_id', $user->id)
            ->delete();

        return back()->with('success', 'You have been unregistered from the championship.');
    }
}
