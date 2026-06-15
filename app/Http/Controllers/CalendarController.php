<?php

namespace App\Http\Controllers;

use App\Models\Race;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $year  = $request->integer('year',  now()->year);
        $month = $request->integer('month', now()->month);

        $current = Carbon::createFromDate($year, $month, 1)->startOfMonth();

        $races = Race::select(['id','title','game','track','scheduled_at','status','is_championship','max_drivers','championship_id'])
            ->whereBetween('scheduled_at', [
                $current->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY),
                $current->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY),
            ])
            ->orderBy('scheduled_at')
            ->get();

        $races->loadCount('registrations');

        $grouped = $races->groupBy(fn($r) => $r->scheduledAtUk()->format('Y-m-d'));

        $myRaceIds = auth()->check()
            ? auth()->user()->raceRegistrations()->pluck('race_id')->flip()
            : collect();

        $prev = $current->copy()->subMonth();
        $next = $current->copy()->addMonth();

        return view('calendar.index', compact('current', 'grouped', 'myRaceIds', 'prev', 'next'));
    }
}