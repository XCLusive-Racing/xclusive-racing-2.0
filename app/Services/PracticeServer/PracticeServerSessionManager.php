<?php

namespace App\Services\PracticeServer;

use App\Models\PracticeServer;
use App\Models\PracticeServerSession;
use App\Models\Race;

// Keeps a race's practice_server_sessions row in sync with the has_practice_server
// checkbox on the event form. Called from RaceController::store()/update() after the
// race itself has been saved.
class PracticeServerSessionManager
{
    public function __construct(private readonly PracticeWindowCalculator $calculator = new PracticeWindowCalculator())
    {
    }

    // Returns a warning string to flash to the admin, or null if everything is fine.
    public function sync(Race $race, bool $wantsPracticeServer): ?string
    {
        $session = $race->practiceServerSession()->first();

        if (!$wantsPracticeServer) {
            return $this->disable($session);
        }

        $server = PracticeServer::where('is_active', true)->first();

        if (!$server) {
            return 'No active practice server is configured — the practice server checkbox was saved but nothing will be scheduled.';
        }

        // Once pushed, the window is locked in — the event's time may still be edited,
        // but we never rewrite a window that has already gone out to the server.
        if ($session && $session->isPushed()) {
            return null;
        }

        $window = $this->calculator->calculate($race, $server);

        if ($window->isAlreadyPast()) {
            $this->save($session, $race, $server, $window, PracticeServerSession::STATUS_TOO_LATE);

            return sprintf(
                'Practice server window has already passed for this event\'s start time (upload would have been at %s). No practice session will be pushed.',
                $window->uploadAt->timezone('Europe/London')->format('d M Y H:i T')
            );
        }

        $this->save($session, $race, $server, $window, PracticeServerSession::STATUS_SCHEDULED);

        return null;
    }

    private function disable(?PracticeServerSession $session): ?string
    {
        if (!$session) {
            return null;
        }

        if ($session->isPushed()) {
            $session->update(['status' => PracticeServerSession::STATUS_COMPLETED]);
            return null;
        }

        if (in_array($session->status, [PracticeServerSession::STATUS_SCHEDULED, PracticeServerSession::STATUS_TOO_LATE], true)) {
            $session->update(['status' => PracticeServerSession::STATUS_CANCELLED]);
        }

        return null;
    }

    private function save(?PracticeServerSession $session, Race $race, PracticeServer $server, PracticeWindow $window, string $status): void
    {
        $attributes = [
            'practice_server_id' => $server->id,
            'window_start'       => $window->windowStart,
            'upload_at'          => $window->uploadAt,
            'window_end'         => $window->windowEnd,
            'status'             => $status,
        ];

        if ($session) {
            $session->update($attributes);
            return;
        }

        $race->practiceServerSession()->create($attributes);
    }
}
