<?php

namespace App\Console\Commands;

use App\Services\TimeTrials\TimeTrialServerService;
use Illuminate\Console\Command;

class PushTimeTrialServers extends Command
{
    protected $signature = 'time-trials:push-due';

    protected $description = 'Upload each weekly Time Trial event\'s config and entry list to its server again (hourly); the server reads them on its own next restart';

    public function handle(TimeTrialServerService $servers): int
    {
        foreach ($servers->duePushes(now()) as $event) {
            $error = $servers->push($event);
            $error
                ? $this->error("Event #{$event->id}: {$error}")
                : $this->info("Event #{$event->id}: uploaded, {$event->last_entry_count} entries");
        }

        return self::SUCCESS;
    }
}
