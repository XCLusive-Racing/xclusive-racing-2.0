<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\RaceResult;
use App\Models\User;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    public function index(Request $request)
    {
        $games = [
            'acc'     => ['label' => 'ACC Console',      'col' => 'elo_acc',     'sr' => 'sr_acc',     'color' => '#7c3aed'],
            'lmu'     => ['label' => 'Le Mans Ultimate', 'col' => 'elo_lmu',     'sr' => 'sr_lmu',     'color' => '#db2877'],
            'iracing' => ['label' => 'iRacing',          'col' => 'elo_iracing', 'sr' => 'sr_iracing', 'color' => '#2563eb'],
        ];

        $game = $request->input('game', 'acc');
        if (!array_key_exists($game, $games)) {
            $game = 'acc';
        }
        $gameInfo = $games[$game];
        $eloCol   = $gameInfo['col'];
        $srCol    = $gameInfo['sr'];

        $query = User::where($eloCol, '>', 0)->orderByDesc($eloCol);

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('platform_id', 'like', "%{$q}%");
            });
        }

        $drivers = $query->paginate(50)->withQueryString();

        // True overall leaderboard position, independent of the search filter above —
        // otherwise a searched-down result set would rank its rows starting from 1.
        $rankedIds = User::where($eloCol, '>', 0)->orderByDesc($eloCol)->pluck('id');
        $rankMap   = array_flip($rankedIds->all());

        $platformIds = $drivers->pluck('platform_id')->filter()->values()->all();
        $driverMap   = Driver::whereIn('xuid_psid', $platformIds)
            ->get(['id', 'xuid_psid'])
            ->keyBy('xuid_psid');

        return view('drivers.index', compact('drivers', 'games', 'game', 'gameInfo', 'eloCol', 'srCol', 'driverMap', 'rankMap'));
    }

    public function show(Driver $driver)
    {
        $driver->load(['stats', 'trackTimes', 'hotlaps']);

        $trackTimes = $driver->trackTimes
            ->sortBy('track')
            ->values();

        $avgRating = RaceResult::where('player_id', $driver->xuid_psid)
            ->where('session_type', 'race')
            ->whereNotNull('rating_after')
            ->avg('rating_after');

        $linkedUser = \App\Models\User::where('platform_id', $driver->xuid_psid)->first();
        $isSupporter = $linkedUser->is_supporter ?? false;

        return view('drivers.show', compact('driver', 'trackTimes', 'avgRating', 'isSupporter', 'linkedUser'));
    }
}