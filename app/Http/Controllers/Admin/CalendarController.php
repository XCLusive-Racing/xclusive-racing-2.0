<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        $races = Race::whereBetween('scheduled_at', [
                $current->copy()->startOfMonth(),
                $current->copy()->endOfMonth(),
            ])
            ->where('status', '!=', 'closed')
            ->orderBy('scheduled_at')
            ->get()
            ->groupBy(fn($r) => $r->scheduled_at->format('Y-m-d'));

        $prev = $current->copy()->subMonth();
        $next = $current->copy()->addMonth();

        return view('admin.calendar.index', compact('current', 'races', 'prev', 'next'));
    }
}