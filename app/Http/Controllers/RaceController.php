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
use App\Services\AccServerConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RaceController extends Controller
{
    public function index()
    {
        $races = Race::select(['id','title','game','track','scheduled_at','status','is_championship','event_tag','max_drivers','duration_key','image','icon','description','sr_requirement','min_rating','max_rating','car_class','weather','event_format_id'])
            ->with('eventFormat:id,race1_mins,race2_mins')
            ->where('status', '!=', 'finished')
            ->orderBy('scheduled_at')
            ->get();
        $races->loadCount('registrations');

        $eventTags = EventTag::orderBy('name')->get();

        return view('race.index', compact('races', 'eventTags'));
    }

    public function show(Race $race)
    {
        $race->load(['raceClasses', 'registrations.user', 'registrations.raceClass', 'registrations.teamEntry.team', 'raceResults.user', 'eventFormat']);
        $isRegistered   = false;
        $myRegistration = null;
        $userTeam       = null;
        $myTeamEntry    = null;

        if (auth()->check()) {
            $myRegistration = $race->registrations->firstWhere('user_id', auth()->id());
            $isRegistered   = $myRegistration !== null;
            $userTeam       = auth()->user()->ownedRacingTeams()->with('members')->first();
            if ($userTeam) {
                $myTeamEntry = RaceTeamEntry::where('race_id', $race->id)
                    ->where('racing_team_id', $userTeam->id)
                    ->with(['registrations.user', 'startingDriver'])
                    ->first();
            }
        }

        $platformIds = $race->registrations->pluck('user.platform_id')->filter()->values()->all();
        $driverMap   = Driver::whereIn('xuid_psid', $platformIds)
            ->get(['id', 'xuid_psid'])
            ->keyBy('xuid_psid');

        return view('race.show', compact('race', 'isRegistered', 'myRegistration', 'driverMap', 'userTeam', 'myTeamEntry'));
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

        $race->load('ftpServer');

        try {
            DB::transaction(function () use ($race, $raceClassId) {
                RaceRegistration::create([
                    'race_id'       => $race->id,
                    'user_id'       => auth()->id(),
                    'race_class_id' => $raceClassId,
                ]);

                $config     = app(AccServerConfigService::class)->settings($race, $race->ftpServer);
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

        $existingEntry = RaceTeamEntry::where('race_id', $race->id)
            ->where('racing_team_id', $team->id)
            ->exists();

        if ($existingEntry) {
            return back()->with('error', 'Your team is already registered for this race.');
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

        if ($race->max_drivers !== null) {
            $currentCount = $race->registrations()->count();
            if ($currentCount + $selectedIds->count() > $race->max_drivers) {
                return back()->with('error', 'Not enough slots available for all selected drivers.');
            }
        }

        $race->load('ftpServer');

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
                    RaceRegistration::create([
                        'race_id'       => $race->id,
                        'user_id'       => $userId,
                        'team_entry_id' => $entry->id,
                    ]);
                }

                $config     = app(AccServerConfigService::class)->settings($race, $race->ftpServer);
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

    public function unregisterTeam(Race $race)
    {
        if ($race->status !== 'open') {
            return back()->with('error', 'You cannot unregister from a closed race.');
        }

        $team = auth()->user()->ownedRacingTeams()->first();
        if (!$team) {
            return back()->with('error', 'You do not own a racing team.');
        }

        $entry = RaceTeamEntry::where('race_id', $race->id)
            ->where('racing_team_id', $team->id)
            ->first();

        if (!$entry) {
            return back()->with('error', 'Your team is not registered for this race.');
        }

        DB::transaction(function () use ($entry) {
            $entry->registrations()->delete();
            $entry->delete();
        });

        return back()->with('success', 'Your team has been unregistered from ' . $race->title . '.');
    }
}