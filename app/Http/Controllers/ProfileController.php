<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Race;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();

        $results = $user->raceResults()
            ->where('session_type', 'race')
            ->orderByDesc('race_scheduled_at')
            ->get();

        $totalRaces = $results->count();
        $wins       = $results->where('position', 1)->count();
        $podiums    = $results->whereIn('position', [1, 2, 3])->count();
        $winRate    = $totalRaces > 0 ? round(($wins / $totalRaces) * 100) : 0;

        $stats = compact('totalRaces', 'wins', 'podiums', 'winRate');

        // Link user to driver record via platform_id or temp gamertag ID
        $driver = Driver::with(['stats', 'trackTimes'])
            ->where('xuid_psid', $user->platform_id)
            ->orWhere('xuid_psid', 'T_' . strtolower($user->name))
            ->orWhere('gamertag', $user->name)
            ->first();

        $myEvents = Race::whereHas('registrations', fn($q) => $q->where('user_id', $user->id))
            ->where('status', '!=', 'finished')
            ->where('scheduled_at', '>', now())
            ->orderBy('scheduled_at')
            ->take(6)
            ->get();

        return view('profile.show', compact('user', 'results', 'stats', 'driver', 'myEvents'));
    }

    public function edit()
    {
        return view('profile.edit', ['user' => Auth::user()]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'country'    => 'nullable|string|max:100',
            'team'       => 'nullable|string|max:255',
            'car_number' => 'nullable|integer|min:1|max:9999',
            'car_model'  => 'nullable|string|max:100',
            'game'       => 'nullable|in:acc,lmu,iracing',
            'avatar'     => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('avatar')) {
            // Delete old avatar
            if ($user->banner) {
                if (str_starts_with($user->banner, 'http')) {
                    // R2 — extract the path after the base URL and delete from disk
                    $diskUrl = rtrim(Storage::disk('media')->url(''), '/');
                    $oldPath = ltrim(str_replace($diskUrl, '', $user->banner), '/');
                    if ($oldPath) Storage::disk('media')->delete($oldPath);
                } elseif (str_starts_with($user->banner, 'images/avatars/')) {
                    $localPath = public_path($user->banner);
                    if (file_exists($localPath)) unlink($localPath);
                }
            }

            $ext      = $request->file('avatar')->getClientOriginalExtension();
            $path     = $request->file('avatar')->storeAs('avatars', Str::uuid() . '.' . $ext, 'media');
            $data['banner'] = Storage::disk('media')->url($path);
        }

        unset($data['avatar']);

        // Password change (optional)
        if ($request->filled('new_password')) {
            $request->validate([
                'current_password' => 'required',
                'new_password'     => 'required|min:8|confirmed',
            ]);

            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'Current password is incorrect.'])->withInput();
            }

            $data['password'] = Hash::make($request->new_password);
        }

        $user->update($data);

        return redirect()->route('profile')->with('success', 'Profile updated successfully.');
    }
}
