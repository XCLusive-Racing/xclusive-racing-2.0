<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ConnectedAccount;
use Laravel\Socialite\Facades\Socialite;

class DiscordController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('discord')->redirect();
    }

    public function callback()
    {
        $discordUser = Socialite::driver('discord')->user();

        $existing = ConnectedAccount::where('provider', 'discord')
            ->where('provider_id', $discordUser->getId())
            ->first();

        if ($existing && $existing->user_id !== auth()->id()) {
            return redirect()->route('profile.edit')
                ->withErrors(['discord' => 'This Discord account is already linked to another profile.']);
        }

        auth()->user()->connectedAccounts()->updateOrCreate(
            ['provider' => 'discord'],
            [
                'provider_id' => $discordUser->getId(),
                'username'    => $discordUser->getNickname() ?? $discordUser->getName(),
                'connected_at' => now(),
            ]
        );

        return redirect()->route('profile.edit')->with('success', 'Discord account connected!');
    }
}