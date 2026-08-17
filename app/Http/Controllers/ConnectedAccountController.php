<?php

namespace App\Http\Controllers;

use App\Models\ConnectedAccount;
use App\Models\User;
use App\Services\PlatformLookupService;
use Illuminate\Http\Request;

class ConnectedAccountController extends Controller
{
    public function store(Request $request, PlatformLookupService $lookup)
    {
        $request->validate([
            'provider' => 'required|in:xbox,psn',
            'username' => 'required|string|max:100',
        ]);

        try {
            $lookupProvider = $request->provider === 'psn' ? 'ps5' : $request->provider;
            $result = $lookup->lookup($lookupProvider, $request->username);
        } catch (\RuntimeException $e) {
            return back()->withErrors([$request->provider . '_username' => $e->getMessage()]);
        }

        $existing = ConnectedAccount::where('provider', $request->provider)
            ->where('provider_id', $result['platform_id'])
            ->where('user_id', '!=', auth()->id())
            ->first();

        if ($existing) {
            return back()->withErrors(['username' => 'This account is already linked to another profile.']);
        }

        $user = auth()->user();

        // Auto-merge an unclaimed import placeholder for this platform_id, if any —
        // carries the historical rating over instead of leaving it stranded, mirroring
        // the merge XboxController does for a primary Xbox sign-in. Only merge if the
        // user's rating is still untouched — otherwise a second connected platform with
        // its own placeholder would silently clobber the rating (and roles) already
        // carried over by the first connect.
        $importAccount = User::where('platform_id', $result['platform_id'])
            ->where('id', '!=', $user->id)
            ->where('email', 'like', '%@import.local')
            ->first();

        $stillDefault = (int) $user->elo_acc === 1500 && (float) $user->sr_acc === 5.00;
        $merged       = false;
        $blockedMerge = false;

        if ($importAccount && $stillDefault) {
            $user->update([
                'elo_acc'     => $importAccount->elo_acc,
                'elo_lmu'     => $importAccount->elo_lmu,
                'elo_iracing' => $importAccount->elo_iracing,
                'sr_acc'      => $importAccount->sr_acc,
                'sr_lmu'      => $importAccount->sr_lmu,
                'sr_iracing'  => $importAccount->sr_iracing,
                'team'        => $importAccount->team ?? $user->team,
                'flag'        => $importAccount->flag ?? $user->flag,
                'role'        => $importAccount->role ?? $user->role,
            ]);
            $roleIds = $importAccount->roles()->pluck('roles.id');
            $user->roles()->sync($roleIds);
            $importAccount->delete();
            $merged = true;
        } elseif ($importAccount) {
            $blockedMerge = true;
        }

        $user->connectedAccounts()->updateOrCreate(
            ['provider' => $request->provider],
            [
                'provider_id'  => $result['platform_id'],
                'username'     => $result['name'],
                'connected_at' => now(),
            ]
        );

        $message = ($request->provider === 'psn' ? 'PlayStation' : 'Xbox') . ' account connected!';
        if ($merged) {
            $message .= ' Your existing rating has been carried over.';
        } elseif ($blockedMerge) {
            $message .= ' This platform also has historical rating data — contact an admin to combine it with your existing rating.';
        }

        return back()->with('success', $message);
    }

    public function destroy(ConnectedAccount $connectedAccount)
    {
        abort_unless($connectedAccount->user_id === auth()->id(), 403);
        $connectedAccount->delete();
        return back()->with('success', $connectedAccount->providerLabel() . ' account disconnected.');
    }
}