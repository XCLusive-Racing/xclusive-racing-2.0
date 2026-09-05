<?php

namespace App\Http\Controllers;

use App\Models\Broadcast;
use Illuminate\View\View;

class LiveController extends Controller
{
    public function index(): View
    {
        $schedule = Broadcast::upcoming()->with('author')->get();

        $twitchChannel = 'trueracingrevival';
        $twitchParent  = request()->getHost();

        return view('live.index', compact('schedule', 'twitchChannel', 'twitchParent'));
    }
}
