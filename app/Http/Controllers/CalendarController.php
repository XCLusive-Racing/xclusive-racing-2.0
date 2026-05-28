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

        // Clamp to valid month
        $current = Carbon::createFromDate($year, $month, 1)->startOfMonth();

        $races = Race::whereBetween('scheduled_at', [
                $current->copy()->startOfMonth(),
                $current->copy()->endOfMonth(),
            ])
            ->orderBy('scheduled_at')
            ->get()
            ->groupBy(fn($r) => $r->scheduled_at->day);

        $prev = $current->copy()->subMonth();
        $next = $current->copy()->addMonth();

        return view('calendar.index', compact('current', 'races', 'prev', 'next'));
    }
}