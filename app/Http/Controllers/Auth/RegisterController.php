<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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
        ]);
    }

    public function store(Request $request, PlatformLookupService $lookup)
    {
        $steamOAuth = $request->platform === 'steam' && session('steam_platform_id');

        $rules = [
            'email'            => 'required|email|unique:users',
            'password'         => 'required|min:8|confirmed',
            'country'          => 'required|string|max:100',
            'platform'         => 'required|in:steam,ps5,xbox',
            'team'             => 'nullable|string|max:255',
            'privacy_accepted' => 'accepted',
        ];

        if (!$steamOAuth) {
            $rules['gamertag'] = 'required|string|max:255';
        }

        $request->validate($rules);

        if ($steamOAuth) {
            $profile = [
                'platform_id' => session('steam_platform_id'),
                'name'        => session('steam_name'),
            ];
            session()->forget(['steam_platform_id', 'steam_name']);
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
        if (!$existing) {
            $normalizedName = strtolower(preg_replace('/#\d+$/', '', $profile['name']));
            $existing = User::where(function ($q) use ($normalizedName, $profile) {
                    $q->where('platform_id', 'T_' . $normalizedName)
                      ->orWhere('platform_id', 'T_' . strtolower($profile['name']));
                })
                ->where('email', 'like', '%@import.local')
                ->first();
        }

        if ($existing) {
            // Imported placeholder — driver claims their account by linking email + password
            if (str_ends_with($existing->email, '@import.local')) {
                $existing->update([
                    'name'                => $profile['name'],
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

        $user = User::create([
            'name'                => $profile['name'],
            'email'               => $request->email,
            'password'            => Hash::make($request->password),
            'country'             => $request->country,
            'platform'            => $request->platform,
            'platform_id'         => $profile['platform_id'],
            'team'                => $request->team,
            'elo_acc'             => 1500,
            'elo_lmu'             => 1500,
            'elo_iracing'         => 1500,
            'privacy_accepted_at' => now(),
        ]);

        Auth::login($user);

        return redirect()->route('profile');
    }
}