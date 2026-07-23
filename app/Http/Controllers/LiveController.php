<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class LiveController extends Controller
{
    public function index(): View
    {
        $schedule = [
            [
                'title'    => 'XCLusive GT3 Championship — Round 4',
                'subtitle' => 'Nürburgring GP · ACC',
                'date'     => Carbon::parse('2026-07-26 20:00:00'),
                'series'   => 'GT3',
                'color'    => '#cc0000',
            ],
            [
                'title'    => 'GT4 Trophy — Round 6',
                'subtitle' => 'Brands Hatch Indy · ACC',
                'date'     => Carbon::parse('2026-08-02 19:30:00'),
                'series'   => 'GT4',
                'color'    => '#7c3aed',
            ],
            [
                'title'    => 'LMU Endurance Series — 4 Hour Le Mans',
                'subtitle' => 'Circuit de la Sarthe · Le Mans Ultimate',
                'date'     => Carbon::parse('2026-08-09 18:00:00'),
                'series'   => 'ENDURANCE',
                'color'    => '#0891b2',
            ],
            [
                'title'    => 'XCLusive GT3 Championship — Round 5',
                'subtitle' => 'Silverstone GP · ACC',
                'date'     => Carbon::parse('2026-08-23 20:00:00'),
                'series'   => 'GT3',
                'color'    => '#cc0000',
            ],
            [
                'title'    => 'iRacing Weekly — GT World Challenge',
                'subtitle' => 'Road Atlanta · iRacing',
                'date'     => Carbon::parse('2026-08-30 20:00:00'),
                'series'   => 'iRACING',
                'color'    => '#1d4ed8',
            ],
        ];

        $twitchChannel = 'trueracingrevival';
        $twitchParent  = request()->getHost();

        return view('live.index', compact('schedule', 'twitchChannel', 'twitchParent'));
    }
}
