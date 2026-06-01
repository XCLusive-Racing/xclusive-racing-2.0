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
        return view('auth.register');
    }

    public function store(Request $request, PlatformLookupService $lookup)
    {
        $request->validate([
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'country'  => 'required|string|max:100',
            'platform' => 'required|in:steam,ps5,xbox',
            'gamertag' => 'required|string|max:255',
            'team'     => 'nullable|string|max:255',
        ]);

        try {
            $profile = $lookup->lookup($request->platform, $request->gamertag);
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['gamertag' => $e->getMessage()]);
        }

        if (User::where('platform_id', $profile['platform_id'])->where('platform', $request->platform)->exists()) {
            return back()->withInput()->withErrors(['gamertag' => 'This platform account is already registered.']);
        }

        $user = User::create([
            'name'        => $profile['name'],
            'email'       => $request->email,
            'password'    => Hash::make($request->password),
            'country'     => $request->country,
            'platform'    => $request->platform,
            'platform_id' => $profile['platform_id'],
            'team'        => $request->team,
            'elo_acc'     => 1200,
            'elo_lmu'     => 1200,
            'elo_iracing' => 1200,
        ]);

        Auth::login($user);

        return redirect()->route('profile');
    }
}