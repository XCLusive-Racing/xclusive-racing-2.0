<?php

namespace App\Console\Commands;

use App\Models\TimeTrialEvent;
use App\Services\TimeTrials\TimeTrialFinalizer;
use App\Services\TimeTrials\TimeTrialResultCollector;
use Illuminate\Console\Command;

class CollectTimeTrialResults extends Command
{
    protected $signature = 'time-trials:collect-results';

    protected $description = 'Collect the hourly result files of live weekly Time Trial events, and finalize events that have ended';

    public function handle(TimeTrialResultCollector $collector, TimeTrialFinalizer $finalizer): int
    {
        $events = TimeTrialEvent::published()
            ->whereNull('finalized_at')
            ->whereNotNull('ftp_server_id')
            ->where('starts_at', '<', now())
            ->with('server')
            ->get();

        foreach ($events as $event) {
            $summary = $collector->collect($event);
            $summary['error']
                ? $this->error("Event #{$event->id}: {$summary['error']}")
                : $this->info("Event #{$event->id}: {$summary['files']} new file(s), {$summary['laps']} lap(s)");

            if (! $summary['error'] && $finalizer->isDue($event) && $finalizer->finalize($event)) {
                $this->info("Event #{$event->id}: finalized");
            }
        }

        return self::SUCCESS;
    }
}
