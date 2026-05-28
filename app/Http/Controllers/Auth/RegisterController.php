<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|unique:users',
            'password'    => 'required|min:8',
            'country'     => 'required|string|max:100',
            'platform'    => 'required|in:steam,ps5,xbox',
            'platform_id' => 'required|string|max:255',
            'team'        => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'name'        => $request->name,
            'email'       => $request->email,
            'password'    => Hash::make($request->password),
            'country'     => $request->country,
            'platform'    => $request->platform,
            'platform_id' => $request->platform_id,
            'team'        => $request->team,
            'elo_acc'     => 1500,
            'elo_lmu'     => 1500,
            'elo_iracing' => 1500,
        ]);

        Auth::login($user);

        return redirect()->route('profile');
    }
}
