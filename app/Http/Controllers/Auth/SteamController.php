<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SteamController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('steam')->redirect();
    }

    public function callback()
    {
        $steamUser = Socialite::driver('steam')->user();
        $platformId = 'S' . $steamUser->getId();

        $user = User::where('platform', 'steam')
            ->where('platform_id', $platformId)
            ->first();

        if (!$user) {
            return redirect()->route('login')
                ->withErrors(['email' => 'No XCLusive Racing account found for this Steam profile. Please register first.']);
        }

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        return redirect()->intended(route('profile'));
    }
}