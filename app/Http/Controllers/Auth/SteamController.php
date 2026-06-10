<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ConnectedAccount;
use App\Models\User;
use GuzzleHttp\Client;
use Laravel\Socialite\Facades\Socialite;

class SteamController extends Controller
{
    private function driver()
    {
        $driver = Socialite::driver('steam');

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
        $steamUser  = $this->driver()->user();
        $platformId = 'S' . $steamUser->getId();

        if (auth()->check()) {
            $existing = ConnectedAccount::where('provider', 'steam')
                ->where('provider_id', $platformId)
                ->where('user_id', '!=', auth()->id())
                ->first();

            if ($existing) {
                return redirect()->route('profile.edit')
                    ->withErrors(['steam' => 'This Steam account is already linked to another profile.']);
            }

            auth()->user()->connectedAccounts()->updateOrCreate(
                ['provider' => 'steam'],
                [
                    'provider_id'  => $platformId,
                    'username'     => $steamUser->getName(),
                    'connected_at' => now(),
                ]
            );

            return redirect()->route('profile.edit')->with('success', 'Steam account connected!');
        }

        $existing = User::where('platform', 'steam')
            ->where('platform_id', $platformId)
            ->first();

        if ($existing) {
            return redirect()->route('login')
                ->withErrors(['email' => 'This Steam account is already registered. Please sign in instead.']);
        }

        session([
            'steam_platform_id' => $platformId,
            'steam_name'        => $steamUser->getName(),
        ]);

        return redirect()->route('register');
    }
}