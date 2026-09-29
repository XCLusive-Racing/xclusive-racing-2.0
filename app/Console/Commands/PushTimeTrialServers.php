<?php

namespace App\Console\Commands;

use App\Services\TimeTrials\TimeTrialServerService;
use Illuminate\Console\Command;

class PushTimeTrialServers extends Command
{
    protected $signature = 'time-trials:push-due';

    protected $description = 'Push each live weekly Time Trial event (config + entry list) to its server ahead of the next restart';

    public function handle(TimeTrialServerService $servers): int
    {
        foreach ($servers->duePushes(now()) as ['event' => $event, 'restart' => $restart]) {
            $error = $servers->push($event, $restart);
            $error
                ? $this->error("Event #{$event->id}: {$error}")
                : $this->info("Event #{$event->id}: pushed {$event->last_entry_count} entries for the {$restart->timezone('Europe/London')->format('H:i')} restart");
        }

        return self::SUCCESS;
    }
}
