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
        $users = User::with('roles')->orderBy('name')->get();
        $roles = Role::orderBy('id')->get();

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function edit(User $user)
    {
        $user->load('roles');
        $roles = Role::orderBy('id')->get();

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
            'banner'      => 'nullable|url|max:500',
            'game'        => 'nullable|in:acc,lmu,iracing',
            'team'        => 'nullable|string|max:100',
            'elo_acc'     => 'required|integer|min:0',
            'elo_lmu'     => 'required|integer|min:0',
            'elo_iracing' => 'required|integer|min:0',
        ]);

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