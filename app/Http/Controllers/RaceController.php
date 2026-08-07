<?php

namespace App\Http\Controllers;

use App\Models\EventTag;
use App\Models\Message;
use App\Models\Race;
use App\Models\RaceClass;
use App\Models\RaceRegistration;
use App\Models\User;
use App\Services\AccServerConfigService;
use Illuminate\Http\Request;

class RaceController extends Controller
{
    public function index()
    {
        $races = Race::select(['id','title','game','track','scheduled_at','status','is_championship','event_tag','max_drivers','duration_key','image','icon','description','sr_requirement','min_rating'])
            ->where('status', '!=', 'finished')
            ->orderBy('scheduled_at')
            ->get();
        $races->loadCount('registrations');

        $eventTags = EventTag::orderBy('name')->get();

        return view('race.index', compact('races', 'eventTags'));
    }

    public function show(Race $race)
    {
        $race->load(['raceClasses', 'registrations.user', 'registrations.raceClass', 'raceResults.user', 'eventFormat']);
        $isRegistered = false;
        $myRegistration = null;

        if (auth()->check()) {
            $myRegistration = $race->registrations->firstWhere('user_id', auth()->id());
            $isRegistered = $myRegistration !== null;
        }

        return view('race.show', compact('race', 'isRegistered', 'myRegistration'));
    }

    public function register(Request $request, Race $race)
    {
        if (auth()->user()->isSuspended()) {
            return back()->with('error', 'Your account has been suspended. Please contact an administrator.');
        }

        if ($race->status !== 'open') {
            return back()->with('error', 'Registration is closed for this race.');
        }

        if ($race->isRegistered(auth()->user())) {
            return back()->with('error', 'You are already registered for this race.');
        }

        if ($failure = $this->requirementFailure(auth()->user(), $race->game, $race->sr_requirement, $race->min_rating)) {
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

            if ($failure = $this->requirementFailure(auth()->user(), $race->game, $raceClass->sr_requirement, $raceClass->min_rating)) {
                return back()->with('error', $failure);
            }

            $raceClassId = $raceClass->id;
        } else {
            if ($race->isFull()) {
                return back()->with('error', 'This race is full.');
            }
        }

        RaceRegistration::create([
            'race_id'       => $race->id,
            'user_id'       => auth()->id(),
            'race_class_id' => $raceClassId,
        ]);

        $server = $race->ftpServer;
        $config = app(AccServerConfigService::class)->settings($race, $server);
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

        return back()->with('success', 'You have been registered for ' . $race->title . '!');
    }

    // Checks the driver's per-game rating (User.elo_{game}/sr_{game} — the same numbers
    // shown on the leaderboard) against an SR/rating requirement. Returns an error message
    // if they don't qualify, or null if they do. Games with no tracked rating (e.g. AC Rally)
    // can't be checked, so requirements are skipped rather than blocking everyone.
    private function requirementFailure(User $user, string $game, ?string $srRequirement, ?string $minRating): ?string
    {
        if (!in_array($game, ['acc', 'lmu', 'iracing'])) {
            return null;
        }

        if ($srRequirement) {
            $userSr = (float) ($user->{"sr_{$game}"} ?? 0);
            if ($userSr < (float) $srRequirement) {
                return 'You need at least SR ' . number_format((float) $srRequirement, 1)
                    . ' to register (yours: ' . number_format($userSr, 1) . ').';
            }
        }

        if ($minRating && $minRating !== 'all') {
            $threshold = collect(User::ranks())->firstWhere('slug', $minRating)['min'] ?? 0;
            $userElo   = (int) ($user->{"elo_{$game}"} ?? 0);
            if ($userElo < $threshold) {
                return 'You need at least ' . ucfirst($minRating) . ' rank to register for this class.';
            }
        }

        return null;
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
}