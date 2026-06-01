<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordSetupController extends Controller
{
    public function show(): View
    {
        return view('auth.password-setup');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $request->user()->update([
            'password'          => Hash::make($request->password),
            'must_set_password' => false,
        ]);

        return redirect()->route('home')->with('success', 'Password set successfully. Welcome!');
    }
}