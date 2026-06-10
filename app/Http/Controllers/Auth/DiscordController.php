<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ConnectedAccount;
use GuzzleHttp\Client;
use Laravel\Socialite\Facades\Socialite;

class DiscordController extends Controller
{
    private function driver()
    {
        $driver = Socialite::driver('discord');

        if (app()->environment('local')) {
            $driver->setHttpClient(new Client(['verify' => false]));
        }

        return $driver;
    }

    public function redirect()
    {
        return $this->driver()->redirect();
    }

    public function callback()
    {
        $discordUser = $this->driver()->user();

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