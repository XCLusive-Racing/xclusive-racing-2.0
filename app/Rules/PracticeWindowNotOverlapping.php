<?php

namespace App\Rules;

use App\Models\PracticeServer;
use App\Models\PracticeServerSession;
use App\Models\Race;
use App\Services\PracticeServer\PracticeWindowCalculator;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

// Validates that enabling the practice server for this event's scheduled_at wouldn't
// overlap another event's already-occupying practice window on the same server. Only
// one event can occupy the practice server at a time, so this blocks the save with a
// message naming the conflicting event rather than silently double-booking it.
class PracticeWindowNotOverlapping implements ValidationRule
{
    public function __construct(private readonly ?int $excludingRaceId = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $server = PracticeServer::where('is_active', true)->first();

        if (!$server) {
            return; // nothing to conflict with — no active practice server configured
        }

        // Matches how RaceController itself parses this same raw datetime-local
        // value — it has no offset, and admins enter it as Europe/London wall-clock.
        $startsAt = Carbon::createFromFormat('Y-m-d\TH:i', $value, 'Europe/London')->utc();
        $race     = new Race(['scheduled_at' => $startsAt]);

        $window = (new PracticeWindowCalculator())->calculate($race, $server);

        $conflict = PracticeServerSession::occupying()
            ->where('practice_server_id', $server->id)
            ->when($this->excludingRaceId, fn ($q) => $q->where('race_id', '!=', $this->excludingRaceId))
            ->where('window_start', '<', $window->windowEnd)
            ->where('window_end', '>', $window->windowStart)
            ->with('race:id,title,scheduled_at')
            ->first();

        if (!$conflict) {
            return;
        }

        $fail(sprintf(
            'The practice server is already booked by "%s" from %s to %s. Only one event can use the practice server at a time.',
            $conflict->race->title ?? 'another event',
            $conflict->window_start->timezone('Europe/London')->format('d M Y H:i'),
            $conflict->window_end->timezone('Europe/London')->format('H:i T')
        ));
    }
}
