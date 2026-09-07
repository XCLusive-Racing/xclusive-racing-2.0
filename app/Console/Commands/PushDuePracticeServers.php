<?php

namespace App\Console\Commands;

use App\Jobs\PushPracticeServerConfigJob;
use App\Models\PracticeServerSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PushDuePracticeServers extends Command
{
    protected $signature = 'practice:push-due';

    protected $description = 'Dispatches the practice server config push for sessions whose upload time has arrived';

    public function handle(): int
    {
        $due = PracticeServerSession::where('status', PracticeServerSession::STATUS_SCHEDULED)
            ->where('upload_at', '<=', now())
            ->pluck('id');

        foreach ($due as $id) {
            // Row lock + status transition inside one transaction guards against two
            // overlapping scheduler runs both dispatching the same session's push.
            $dispatched = DB::transaction(function () use ($id) {
                $session = PracticeServerSession::whereKey($id)->lockForUpdate()->first();

                if (!$session || $session->status !== PracticeServerSession::STATUS_SCHEDULED) {
                    return false;
                }

                $session->update(['status' => PracticeServerSession::STATUS_PUSHING]);
                return true;
            });

            if ($dispatched) {
                PushPracticeServerConfigJob::dispatch(PracticeServerSession::findOrFail($id));
                $this->info("Dispatched push for practice session #{$id}.");
            }
        }

        // Housekeeping: a live session whose event has finished is done.
        $completed = PracticeServerSession::where('status', PracticeServerSession::STATUS_LIVE)
            ->where('window_end', '<', now())
            ->update(['status' => PracticeServerSession::STATUS_COMPLETED]);

        if ($completed > 0) {
            $this->info("Marked {$completed} finished practice session(s) as completed.");
        }

        return self::SUCCESS;
    }
}
