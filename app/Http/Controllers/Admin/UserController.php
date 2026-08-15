<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $rolesOnlyCount = User::whereHas('roles', fn($q) => $q->where('slug', '!=', 'driver'))->count();

        return view('admin.users.index', compact('rolesOnlyCount'));
    }

    public function data(Request $request)
    {
        $query = User::query()
            ->select(['id', 'name', 'email', 'banner', 'platform', 'platform_id', 'team', 'is_supporter', 'is_suspended', 'created_at']);

        if ($request->boolean('roles_only')) {
            $query->whereHas('roles', fn($q) => $q->where('slug', '!=', 'driver'));
        }

        $recordsTotal    = User::count();
        $recordsFiltered = (clone $query)->count();

        if ($search = trim((string) $request->input('search.value'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('team', 'like', "%{$search}%")
                    ->orWhere('platform_id', 'like', "%{$search}%");
            });
            $recordsFiltered = (clone $query)->count();
        }

        $orderableColumns = ['name', 'platform_id', null, null, 'created_at', 'team', null, null];
        $orderColumn      = $orderableColumns[(int) $request->input('order.0.column', 0)] ?? 'name';
        $orderDir         = $request->input('order.0.dir') === 'desc' ? 'desc' : 'asc';

        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);

        $users = $query->with(['roles', 'connectedAccounts'])
            ->orderBy($orderColumn, $orderDir)
            ->skip($start)
            ->take($length > 0 ? $length : 25)
            ->get();

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $users->map(fn (User $user) => $this->rowToArray($user))->values(),
        ]);
    }

    private function rowToArray(User $user): array
    {
        $avatar = $user->banner
            ? '<img src="' . e($user->avatarUrl()) . '" alt="" class="rounded-circle flex-shrink-0" style="width:32px;height:32px;object-fit:cover">'
            : '<div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-black flex-shrink-0" style="width:32px;height:32px;font-size:.75rem;background:linear-gradient(135deg,#7c3aed,#db2777)">' . e(strtoupper(substr($user->name, 0, 1))) . '</div>';

        $driver = '<div class="d-flex align-items-center gap-2">' . $avatar
            . '<div><div class="fw-bold text-dark">' . e($user->name) . '</div>'
            . '<div class="text-secondary" style="font-size:.72rem">' . e($user->email) . '</div></div></div>';

        $id = $user->platform_id
            ? '<div class="d-flex align-items-center gap-1"><span class="badge fw-bold" style="background:#f3f4f6;color:#6b7280;font-size:.65rem;padding:2px 6px">' . e(strtoupper($user->platform ?? '?')) . '</span><code style="font-size:.75rem;color:#374151">' . e($user->platform_id) . '</code></div>'
            : '<span class="text-secondary">—</span>';

        $connected = $user->connectedAccounts->isEmpty()
            ? '<span class="text-secondary">—</span>'
            : '<div class="d-flex gap-2 align-items-center">' . $user->connectedAccounts->map(
                fn ($a) => '<span title="' . e($a->providerLabel()) . '" style="color:' . e($a->providerColor()) . ';font-size:1rem">' . $a->providerIcon() . '</span>'
            )->implode('') . '</div>';

        $status = $user->is_suspended
            ? '<span style="font-size:.68rem;font-weight:700;padding:2px 8px;border-radius:4px;background:#fee2e2;color:#dc2626">Suspended</span>'
            : ($user->is_supporter
                ? '<span style="font-size:.68rem;font-weight:700;padding:2px 8px;border-radius:4px;background:#fef3c7;color:#d97706">★ Supporter</span>'
                : '<span class="text-secondary">—</span>');

        $joined = '<span style="font-size:.8rem;color:#6b7280">' . e($user->created_at->format('d M Y')) . '</span>';

        $team = '<span class="text-secondary" style="font-size:.82rem">' . e($user->team ?? '—') . '</span>';

        $roleColors = ['owner' => ['#f3e8ff', '#7c3aed'], 'admin' => ['#fce7f3', '#db2777'], 'moderator' => ['#dbeafe', '#2563eb'], 'event_manager' => ['#fef3c7', '#d97706'], 'steward' => ['#e0f2fe', '#0891b2'], 'driver' => ['#d1fae5', '#059669'], 'broadcaster' => ['#ffe4e6', '#e11d48']];
        $roles = $user->roles->sortBy('sort_order')->map(function ($role) use ($roleColors) {
            $rc = $roleColors[$role->slug] ?? ['#f3f4f6', '#374151'];
            return '<span class="badge fw-bold" style="background:' . $rc[0] . ';color:' . $rc[1] . ';font-size:.7rem;padding:4px 8px;border-radius:6px">' . e($role->name) . '</span>';
        })->implode('');
        $rolesHtml = '<div class="d-flex flex-wrap gap-1">' . $roles . '</div>';

        $actions = '<div class="d-flex gap-2 justify-content-end"><a href="' . e(route('admin.users.edit', $user)) . '" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase" style="font-size:.72rem;padding:5px 12px;border-radius:6px">Edit</a></div>';

        return [$driver, $id, $connected, $status, $joined, $team, $rolesHtml, $actions];
    }

    public function edit(User $user)
    {
        $user->load('roles');
        $roles = Role::orderBy('sort_order')->get();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'roles'       => 'nullable|array',
            'roles.*'     => 'exists:roles,slug',
            'country'     => 'nullable|string|max:100',
            'platform'    => 'nullable|in:steam,ps5,xbox',
            'platform_id' => 'nullable|string|max:60',
            'car_number'  => 'nullable|integer|min:1|max:9999',
            'car_model'   => 'nullable|string|max:100',
            'banner'      => 'nullable|string|max:500',
            'game'                   => 'nullable|in:acc,lmu,iracing',
            'team'                   => 'nullable|string|max:100',
            'display_name_preference'=> 'nullable|in:gamertag,name',
            'is_supporter'           => 'nullable|boolean',
            'is_suspended'           => 'nullable|boolean',
            'suspension_reason'      => 'nullable|string|max:500',
            'suspended_until'        => 'nullable|date|after:now',
            'elo_acc'     => 'required|integer|min:0',
            'elo_lmu'     => 'required|integer|min:0',
            'elo_iracing' => 'required|integer|min:0',
            'sr_acc'      => 'required|numeric|min:0|max:9.99',
            'sr_lmu'      => 'required|numeric|min:0|max:9.99',
            'sr_iracing'  => 'required|numeric|min:0|max:9.99',
        ]);

        $data['is_supporter'] = $request->boolean('is_supporter');
        $data['is_suspended'] = $request->boolean('is_suspended');

        if (!$data['is_suspended']) {
            $data['suspended_until'] = null;
            $data['suspension_reason'] = null;
        }

        $user->update(\Illuminate\Support\Arr::except($data, ['roles']));

        if ($user->id !== auth()->id()) {
            $roleIds = Role::whereIn('slug', $request->input('roles', []))->pluck('id');
            $user->roles()->sync($roleIds);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User "' . $user->name . '" updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User "' . $name . '" has been deleted.');
    }
}