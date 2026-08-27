<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\RacingTeam;
use App\Models\RacingTeamInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RacingTeamController extends Controller
{
    public function index()
    {
        $user = Auth::user()->load([
            'ownedRacingTeams.members',
            'ownedRacingTeams.invitations.user',
            'racingTeams.owner',
            'racingTeams.members',
            'racingTeamInvitations.team.owner',
        ]);
        return view('racing-teams.index', compact('user'));
    }

    public function searchUsers(Request $request)
    {
        $q    = $request->string('q')->trim()->value();
        $team = RacingTeam::with(['members', 'invitations'])->findOrFail($request->integer('team_id'));

        abort_unless(Auth::id() === $team->owner_id, 403);

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $existingIds = $team->members->pluck('id')
            ->merge($team->invitations->pluck('user_id'))
            ->push($team->owner_id);

        $users = User::where('name', 'like', '%' . $q . '%')
            ->whereNull('deleted_at')
            ->whereNotIn('id', $existingIds)
            ->select('id', 'name')
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->map(fn($u) => ['id' => $u->id, 'name' => $u->displayName()]);

        return response()->json($users);
    }

    public function store(Request $request)
    {
        if (RacingTeam::where('owner_id', Auth::id())->exists()) {
            return back()->withErrors(['team' => 'You already own a team. Delete it first to create a new one.']);
        }

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
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $user = User::findOrFail($data['user_id']);

        if ($user->id === $team->owner_id) {
            return back()->withErrors(['team' => 'You are already the team owner.']);
        }

        if ($team->members->contains($user->id)) {
            return back()->withErrors(['team' => $user->displayName() . ' is already a member.']);
        }

        $invitation = RacingTeamInvitation::firstOrCreate([
            'racing_team_id' => $team->id,
            'user_id'        => $user->id,
        ]);

        if ($invitation->wasRecentlyCreated) {
            Message::create([
                'user_id' => $user->id,
                'title'   => 'Team Invitation: ' . $team->name,
                'body'    => Auth::user()->displayName() . ' has invited you to join their racing team ' . $team->name . '. Go to My Team to accept or decline.',
                'type'    => 'team_invitation',
            ]);
        }

        return back()->with('team_success', 'Invite sent to ' . $user->displayName() . '.');
    }

    public function acceptInvite(RacingTeamInvitation $invitation)
    {
        abort_unless(Auth::id() === $invitation->user_id, 403);

        $invitation->team->members()->syncWithoutDetaching([$invitation->user_id]);
        $invitation->delete();

        return back()->with('team_success', 'You joined ' . $invitation->team->name . '!');
    }

    public function declineInvite(RacingTeamInvitation $invitation)
    {
        abort_unless(Auth::id() === $invitation->user_id, 403);

        $invitation->delete();

        return back()->with('team_success', 'Invite declined.');
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

    public function updateLogo(Request $request, RacingTeam $team)
    {
        abort_unless(Auth::id() === $team->owner_id, 403);

        $request->validate([
            'logo' => 'required|image|max:2048|mimes:jpg,jpeg,png,webp',
        ]);

        if ($team->logo) {
            \Storage::disk('media')->delete($team->logo);
        }

        $path = $request->file('logo')->store('team-logos', 'media');
        $team->update(['logo' => $path]);

        return back()->with('team_success', 'Team logo updated.');
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