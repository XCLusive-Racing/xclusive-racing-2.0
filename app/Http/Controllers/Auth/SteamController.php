<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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
        $steamUser = $this->driver()->user();
        $platformId = 'S' . $steamUser->getId();

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