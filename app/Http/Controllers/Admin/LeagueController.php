<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Championship;
use App\Models\FtpServer;
use App\Models\League;
use App\Models\LeagueUser;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\DiscordRoleService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LeagueController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->canManage()) {
            // withTrashed(): an archived league is now a real soft delete (see
            // archive()) — without this it would vanish from the one screen that
            // still needs to show it, with no way left to restore it.
            $leagues = League::withoutTenantScope()->withTrashed()->withCount('members')->orderBy('name')->get();
            return view('admin.leagues.index', compact('leagues'));
        }

        // A league manager/steward attached to exactly one league lands straight on
        // it, rather than an index of one — they should never have to guess which
        // league they're acting in.
        $leagues = League::withTrashed()->orderBy('name')->get();

        if ($leagues->count() === 1) {
            return redirect()->route('admin.leagues.edit', $leagues->first());
        }

        return view('admin.leagues.index', compact('leagues'));
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->canManage(), 403);

        return view('admin.leagues.create');
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->canManage(), 403);

        $data = $request->validate([
            'name'                         => 'required|string|max:150',
            'slug'                         => 'required|alpha_dash|max:150|unique:leagues,slug',
            'primary_color'                => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color'                 => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'description'                  => 'nullable|string|max:5000',
            'discord_invite_url'           => 'nullable|url|max:255',
            'discord_guild_id'             => 'nullable|string|max:32',
            'website_url'                  => 'nullable|url|max:255',
            'requires_discord_membership'  => 'nullable|boolean',
            'status'                       => 'required|in:draft,active',
            'logo'                         => 'nullable|image|max:4096',
            'banner'                       => 'nullable|image|max:8192',
        ]);

        $data['requires_discord_membership'] = $request->boolean('requires_discord_membership');
        $data['logo']   = $this->resolveUpload($request, 'logo', null);
        $data['banner'] = $this->resolveUpload($request, 'banner', null);

        $league = League::create($data);

        AuditLogger::record($request->user(), $league, 'league.created', $data);

        return redirect()->route('admin.leagues.edit', $league)->with('success', 'League created.');
    }

    public function edit(Request $request, League $league)
    {
        $user      = $request->user();
        $isAdmin   = $user->canManage();
        $isManager = $user->managesLeague($league);
        $isSteward = $user->stewardsLeague($league);

        abort_unless($isAdmin || $isManager || $isSteward, 403);

        $canEdit = $isAdmin || $isManager;
        $members = $isAdmin ? $league->memberships()->with('user')->get() : collect();
        $users   = $isAdmin ? User::orderBy('name')->get(['id', 'name']) : collect();

        // A league's own server list is visible to whoever can edit the league (admin
        // or its manager) — but the cross-league picker of XCL's other unassigned
        // servers, and every other league's own list, stays admin-only.
        $servers           = $canEdit ? $league->ftpServers()->orderBy('name')->get() : collect();
        $unassignedServers = $isAdmin ? FtpServer::withoutTenantScope()->where('league_id', League::system()->id)->orderBy('name')->get() : collect();

        // null = "couldn't check" (no bot token configured, or Discord unreachable),
        // distinct from a real true/false — see DiscordRoleService::isBotInGuild().
        $discordBotInGuild = $league->discord_guild_id
            ? app(DiscordRoleService::class)->isBotInGuild($league->discord_guild_id)
            : null;

        return view('admin.leagues.edit', compact('league', 'isAdmin', 'canEdit', 'members', 'users', 'servers', 'unassignedServers', 'discordBotInGuild'));
    }

    public function update(Request $request, League $league)
    {
        $user      = $request->user();
        $isAdmin   = $user->canManage();
        $isManager = $user->managesLeague($league);

        abort_unless($isAdmin || $isManager, 403);

        $rules = [
            'primary_color'       => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color'        => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'description'         => 'nullable|string|max:5000',
            'discord_invite_url'  => 'nullable|url|max:255',
            'website_url'         => 'nullable|url|max:255',
            'logo'                => 'nullable|image|max:4096',
            'banner'              => 'nullable|image|max:8192',
        ];

        // A League Manager edits branding, description and links only — never the
        // league's name/slug/status or its Discord requirement, even if the field
        // is present in the request body. Only the rules an admin passes are ever
        // read out of the request below.
        if ($isAdmin) {
            $rules['name']                        = 'required|string|max:150';
            $rules['slug']                        = 'required|alpha_dash|max:150|unique:leagues,slug,' . $league->id;
            // "archived" is deliberately excluded here — that's now only reachable
            // through archive(), which is owner-only and does a real soft delete.
            $rules['status']                      = 'required|in:draft,active';
            $rules['requires_discord_membership'] = 'nullable|boolean';
            $rules['discord_guild_id']            = 'nullable|string|max:32';
        }

        $data = $request->validate($rules);

        if ($isAdmin) {
            $data['requires_discord_membership'] = $request->boolean('requires_discord_membership');
        }

        $data['logo']   = $this->resolveUpload($request, 'logo', $league->logo);
        $data['banner'] = $this->resolveUpload($request, 'banner', $league->banner);

        $before = $league->only(array_keys($data));
        $league->update($data);

        AuditLogger::record($user, $league, 'league.updated', ['before' => $before, 'after' => $data]);

        return redirect()->route('admin.leagues.edit', $league)->with('success', 'League updated.');
    }

    public function archive(Request $request, League $league)
    {
        // Archiving is a league's "delete" — the only staff action that takes a
        // league out of use — so it's restricted to the owner role specifically,
        // unlike everything else here which any canManage() staff can do. It's a
        // real (Eloquent) soft delete, not just a status flag: League already had
        // the softDeletes() column and SoftDeletes trait sitting unused. Deleting
        // for real means every plain League:: query across the app now excludes
        // an archived league automatically, instead of relying on every one of
        // those call sites to remember to filter status != 'archived' itself.
        abort_unless($request->user()->isOwner(), 403);
        abort_if($league->is_system, 403, 'The XCL league cannot be archived.');

        $league->update(['status' => 'archived']);
        $league->delete();
        AuditLogger::record($request->user(), $league, 'league.archived');

        return back()->with('success', $league->name . ' has been archived.');
    }

    public function restore(Request $request, League $league)
    {
        abort_unless($request->user()->canManage(), 403);

        $league->restore();
        $league->update(['status' => 'draft']);
        AuditLogger::record($request->user(), $league, 'league.restored');

        return back()->with('success', $league->name . ' has been restored to draft.');
    }

    // Real, permanent removal — distinct from archive() above, which is
    // reversible and keeps the league listed (as "Archived") for restoring.
    // Only reachable on an already-archived league, and only when it has no
    // championships left: championships.league_id is nullOnDelete(), so a
    // championship still attached to a force-deleted league would be orphaned
    // with a null league_id — invisible under TenantScope (no bypass for null
    // since Phase 2.5) rather than actually gone, with its rounds/registrations/
    // results silently stranded. Requiring the league to be emptied out first
    // avoids ever creating that state.
    public function destroy(Request $request, League $league)
    {
        abort_unless($request->user()->isOwner(), 403);
        abort_if($league->is_system, 403, 'The XCL league cannot be deleted.');
        abort_unless($league->trashed(), 422, 'Archive the league before deleting it permanently.');

        // withTrashed(): a soft-deleted championship is still a real row that
        // would otherwise get silently orphaned by the same nullOnDelete gap.
        $championshipCount = Championship::withoutTenantScope()->withTrashed()->where('league_id', $league->id)->count();
        abort_if($championshipCount > 0, 422, 'This league still has championships — remove or reassign them first.');

        $name = $league->name;
        AuditLogger::record($request->user(), $league, 'league.deleted');
        $league->forceDelete();

        return redirect()->route('admin.leagues.index')->with('success', $name . ' has been permanently deleted.');
    }

    public function addMember(Request $request, League $league)
    {
        abort_unless($request->user()->canManage(), 403);

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role'    => 'required|in:manager,steward',
        ]);

        $member = $league->memberships()->firstOrCreate($data);
        $member->user->syncLeagueRoleFlags();

        AuditLogger::record($request->user(), $league, 'league.member_added', $data);

        return back()->with('success', 'Member added.');
    }

    public function removeMember(Request $request, League $league, LeagueUser $member)
    {
        abort_unless($request->user()->canManage(), 403);
        abort_unless($member->league_id === $league->id, 404);

        $memberUser = $member->user;
        $removed    = ['user_id' => $member->user_id, 'role' => $member->role];
        $member->delete();
        $memberUser->syncLeagueRoleFlags();

        AuditLogger::record($request->user(), $league, 'league.member_removed', $removed);

        return back()->with('success', 'Member removed.');
    }

    public function assignServer(Request $request, League $league)
    {
        abort_unless($request->user()->canManage(), 403);

        $data = $request->validate([
            'ftp_server_id' => 'required|exists:ftp_servers,id',
        ]);

        $server = FtpServer::withoutTenantScope()->findOrFail($data['ftp_server_id']);
        $server->update(['league_id' => $league->id]);

        AuditLogger::record($request->user(), $league, 'league.server_assigned', ['ftp_server_id' => $server->id]);

        return back()->with('success', $server->name . ' assigned to ' . $league->name . '.');
    }

    public function unassignServer(Request $request, League $league, FtpServer $server)
    {
        $user = $request->user();
        abort_unless($user->canManage() || $user->managesLeague($league), 403);
        abort_unless($server->league_id === $league->id, 404);

        $server->update(['league_id' => League::system()->id]);

        AuditLogger::record($user, $league, 'league.server_unassigned', ['ftp_server_id' => $server->id]);

        return back()->with('success', $server->name . ' unassigned from ' . $league->name . '.');
    }

    // A league brings its own server (its own credentials, its own box) — unlike
    // assignServer above, this creates a brand new FtpServer row rather than
    // reassigning one of XCL's existing ones, so a league manager can do it too.
    public function storeServer(Request $request, League $league)
    {
        $user = $request->user();
        abort_unless($user->canManage() || $user->managesLeague($league), 403);

        $data = $request->validate([
            'name'                   => 'required|string|max:150',
            'host'                   => 'required|string|max:255',
            'port'                   => 'required|integer|min:1|max:65535',
            'username'               => 'required|string|max:100',
            'password'               => 'required|string|max:255',
            'path'                   => 'required|string|max:255',
            'cfg_path'               => 'nullable|string|max:255',
            'server_type'            => 'required|in:rolling,scheduled',
            'reset_start_hour'       => 'required_if:server_type,rolling|integer|min:0|max:23',
            'reset_interval_minutes' => 'required_if:server_type,rolling|integer|min:30|max:1440',
            'game'                   => 'required|in:acc,lmu',
            'platform'               => 'required|in:pc,console,cross',
        ]);

        $data['league_id'] = $league->id;
        $data['active']    = true;

        $server = FtpServer::create($data);

        AuditLogger::record($user, $server, 'league.server_created', $request->only('name', 'host', 'path', 'server_type'));

        return back()->with('success', $server->name . ' added to ' . $league->name . '.');
    }

    private function resolveUpload(Request $request, string $field, ?string $current): ?string
    {
        if ($request->hasFile($field)) {
            $file = $request->file($field);
            return $file->storeAs('images/leagues', Str::uuid() . '.' . $file->getClientOriginalExtension(), 'media');
        }

        if ($request->boolean($field . '_remove')) {
            return null;
        }

        return $current;
    }
}
