<?php

namespace App\Http\Controllers;

use App\Models\Race;
use App\Models\RaceResult;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class EventController extends Controller
{
    private const POINTS_MAP = [1=>25,2=>18,3=>15,4=>12,5=>10,6=>8,7=>6,8=>4,9=>2,10=>1];

    public function getSidebarData(): JsonResponse
    {
        $now = now();

        $nextEvent = Race::select(['id','title','game','track','scheduled_at'])
            ->where('scheduled_at', '>', $now)
            ->orderBy('scheduled_at')
            ->first();

        $upcomingEvents = Race::select(['id','title','game','track','scheduled_at'])
            ->where('scheduled_at', '>', $now)
            ->when($nextEvent, fn($q) => $q->where('id', '!=', $nextEvent->id))
            ->orderBy('scheduled_at')
            ->limit(2)
            ->get()
            ->map(fn($r) => [
                'id'    => $r->id,
                'title' => $r->title,
                'track' => $r->track,
                'time'  => strtoupper($r->scheduledAtUk()->format('D d M')) . ' · ' . $r->scheduledAtUk()->format('g:iA'),
                'url'   => route('events.show', $r),
            ]);

        $leaderboard = User::where('elo_acc', '>', 0)
            ->orderByDesc('elo_acc')
            ->limit(40)
            ->get()
            ->values()
            ->map(fn($u, $i) => [
                'pos'     => $i + 1,
                'name'    => $u->name,
                'country' => strtoupper($u->country ?? 'XX'),
                'gain'    => (int)($u->elo_acc ?? 0),
            ]);

        return response()->json([
            'nextEvent'      => $nextEvent ? [
                'id'      => $nextEvent->id,
                'title'   => $nextEvent->title,
                'track'   => $nextEvent->track,
                'day'     => strtoupper($nextEvent->scheduledAtUk()->format('l')),
                'time'    => strtoupper($nextEvent->scheduledAtUk()->format('gA T')),
                'date'    => $nextEvent->scheduledAtUk()->format('D, M d'),
                'url'     => route('events.show', $nextEvent),
            ] : null,
            'upcomingEvents' => $upcomingEvents,
            'leaderboard'    => $leaderboard,
        ]);
    }
}