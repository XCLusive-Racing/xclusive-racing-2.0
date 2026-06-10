<?php

namespace App\Http\Controllers;

use App\Models\ConnectedAccount;
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

        auth()->user()->connectedAccounts()->updateOrCreate(
            ['provider' => $request->provider],
            [
                'provider_id'  => $result['platform_id'],
                'username'     => $result['name'],
                'connected_at' => now(),
            ]
        );

        return back()->with('success', ($request->provider === 'psn' ? 'PlayStation' : 'Xbox') . ' account connected!');
    }

    public function destroy(ConnectedAccount $connectedAccount)
    {
        abort_unless($connectedAccount->user_id === auth()->id(), 403);
        $connectedAccount->delete();
        return back()->with('success', $connectedAccount->providerLabel() . ' account disconnected.');
    }
}