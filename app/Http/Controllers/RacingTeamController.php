<?php

namespace App\Http\Controllers;

use App\Models\RacingTeam;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RacingTeamController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:40',
            'tag'  => 'required|string|max:6',
        ]);

        $team = RacingTeam::create([
            'name'     => $data['name'],
            'tag'      => strtoupper($data['tag']),
            'owner_id' => Auth::id(),
        ]);

        return back()->with('team_success', 'Team "' . $team->name . '" created.');
    }

    public function addMember(Request $request, RacingTeam $team)
    {
        abort_unless(Auth::id() === $team->owner_id, 403);

        $data = $request->validate([
            'username' => 'required|string',
        ]);

        $user = User::where('name', $data['username'])->first();

        if (! $user) {
            return back()->withErrors(['username' => 'User not found.']);
        }

        if ($user->id === $team->owner_id) {
            return back()->withErrors(['username' => 'You are already the team owner.']);
        }

        $team->members()->syncWithoutDetaching([$user->id]);

        return back()->with('team_success', $user->name . ' added to the team.');
    }

    public function removeMember(Request $request, RacingTeam $team, User $user)
    {
        abort_unless(Auth::id() === $team->owner_id, 403);
        abort_if($user->id === $team->owner_id, 422);

        $team->members()->detach($user->id);

        return back()->with('team_success', $user->name . ' removed from the team.');
    }

    public function destroy(RacingTeam $team)
    {
        abort_unless(Auth::id() === $team->owner_id, 403);

        $team->delete();

        return back()->with('team_success', 'Team deleted.');
    }

    public function leave(RacingTeam $team)
    {
        $user = Auth::user();

        if ($user->id === $team->owner_id) {
            return back()->withErrors(['team' => 'As owner you cannot leave. Delete the team instead.']);
        }

        $team->members()->detach($user->id);

        return back()->with('team_success', 'You left the team.');
    }
}