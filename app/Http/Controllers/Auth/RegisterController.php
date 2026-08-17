<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\User;
use App\Services\PlatformLookupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function create()
    {
        return view('auth.register', [
            'steamId'   => session('steam_platform_id'),
            'steamName' => session('steam_name'),
            'xboxId'    => session('xbox_platform_id'),
            'xboxName'  => session('xbox_name'),
        ]);
    }

    public function store(Request $request, PlatformLookupService $lookup)
    {
        $steamOAuth = $request->platform === 'steam' && session('steam_platform_id');
        $xboxOAuth  = $request->platform === 'xbox'  && session('xbox_platform_id');

        $rules = [
            'email'            => 'required|email|unique:users',
            'password'         => 'required|min:8|confirmed',
            'country'          => 'required|string|max:100',
            'platform'         => 'required|in:steam,ps5,xbox',
            'team'             => 'nullable|string|max:255',
            'privacy_accepted' => 'accepted',
        ];

        if (!$steamOAuth && !$xboxOAuth) {
            $rules['gamertag'] = 'required|string|max:255';
        }

        $request->validate($rules);

        if ($steamOAuth) {
            $profile = [
                'platform_id' => session('steam_platform_id'),
                'name'        => session('steam_name'),
            ];
            session()->forget(['steam_platform_id', 'steam_name']);
        } elseif ($xboxOAuth) {
            $profile = [
                'platform_id' => session('xbox_platform_id'),
                'name'        => session('xbox_name'),
            ];
            session()->forget(['xbox_platform_id', 'xbox_name']);
        } else {
            try {
                $profile = $lookup->lookup($request->platform, $request->gamertag);
            } catch (\RuntimeException $e) {
                return back()->withInput()->withErrors(['gamertag' => $e->getMessage()]);
            }
        }

        $existing = User::where('platform_id', $profile['platform_id'])
            ->where('platform', $request->platform)
            ->first();

        // Fallback: match temp-imported accounts by gamertag (T_ prefix).
        // Strip #xxxx discriminator from both sides so "Name#1234" matches "T_name".
        if (!$existing && $profile['name'] !== null) {
            $normalizedName = strtolower(preg_replace('/#\d+$/', '', $profile['name']));
            $existing = User::where(function ($q) use ($normalizedName, $profile) {
                    $q->where('platform_id', 'T_' . $normalizedName)
                      ->orWhere('platform_id', 'T_' . strtolower($profile['name']));
                })
                ->where('email', 'like', '%@import.local')
                ->first();
        }

        // XUID entered directly but no matching imported account — can't create a new
        // account without a verified gamertag name.
        if ($profile['name'] === null && !$existing) {
            return back()->withInput()->withErrors([
                'gamertag' => 'No account found for this XUID. If you are a new member, enter your Xbox gamertag instead.',
            ]);
        }

        // When user entered their XUID directly, preserve the imported display name.
        $resolvedName = $profile['name'] ?? $existing->name;

        if ($existing) {
            // Imported placeholder — driver claims their account by linking email + password
            if (str_ends_with($existing->email, '@import.local')) {
                $existing->update([
                    'name'                => $resolvedName,
                    'platform_id'         => $profile['platform_id'],
                    'platform'            => $request->platform,
                    'email'               => $request->email,
                    'password'            => Hash::make($request->password),
                    'country'             => $request->country,
                    'team'                => $request->team ?? $existing->team,
                    'must_set_password'   => false,
                    'privacy_accepted_at' => now(),
                ]);
                Auth::login($existing);
                return redirect()->route('profile');
            }

            return back()->withInput()->withErrors(['gamertag' => 'This platform account is already registered.']);
        }

        // Members already tracked in the ratings/driver-stats import (the Driver table,
        // keyed on platform_id) carry their real ACC rating over instead of starting at
        // the 1500 default.
        $driver = Driver::where('xuid_psid', $profile['platform_id'])->first();

        $user = User::create([
            'name'                => $resolvedName,
            'email'               => $request->email,
            'password'            => Hash::make($request->password),
            'country'             => $request->country,
            'platform'            => $request->platform,
            'platform_id'         => $profile['platform_id'],
            'team'                => $request->team,
            'elo_acc'             => $driver->xcl_rating ?? 1500,
            'elo_lmu'             => 1500,
            'elo_iracing'         => 1500,
            'sr_acc'              => $driver->safety_rating ?? 5.00,
            'privacy_accepted_at' => now(),
        ]);

        Auth::login($user);

        return redirect()->route('profile');
    }
}