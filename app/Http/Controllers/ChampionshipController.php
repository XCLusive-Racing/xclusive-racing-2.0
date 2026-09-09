<?php

namespace App\Http\Controllers;

use App\Models\Championship;
use App\Models\ChampionshipClass;
use App\Models\ChampionshipRegistration;
use App\Models\League;
use Illuminate\Http\Request;

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
        $championship->load(['classes', 'registrations.user', 'registrations.championshipClass']);
        $rounds         = $championship->rounds()->where('status', '!=', 'draft')->orderBy('round_number')->get();
        $standings      = $championship->computeStandings();
        $classStandings = $championship->computeClassStandings();

        return view('championships.show', compact('championship', 'rounds', 'standings', 'classStandings'));
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

        if ($championship->isFull() && !$championship->waitlistEnabled()) {
            return back()->with('error', 'Championship is full.');
        }

        if ($failure = $user->requirementFailure($championship->game, $championship->sr_requirement, $championship->min_rating)) {
            return back()->with('error', $failure);
        }

        $classId = null;
        if ($championship->is_multiclass) {
            $request->validate(['championship_class_id' => 'required|exists:championship_classes,id']);
            $classId = $request->championship_class_id;

            $class = ChampionshipClass::where('id', $classId)
                ->where('championship_id', $championship->id)
                ->firstOrFail();

            if ($failure = $user->requirementFailure($championship->game, $class->sr_requirement, $class->min_rating)) {
                return back()->with('error', $failure);
            }
        }

        ChampionshipRegistration::create([
            'championship_id'       => $championship->id,
            'user_id'               => $user->id,
            'championship_class_id' => $classId,
        ]);

        $message = $championship->isRegistrationWaitlisted($user)
            ? 'The championship is full — you have been added to the waiting list.'
            : 'You have been registered for the championship!';

        return back()->with('success', $message);
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
