<?php

namespace App\Console\Commands;

use App\Services\PracticeServer\ChampionshipPracticeService;
use Illuminate\Console\Command;

// Midnight push of every championship's 24h practice server
// (settings.sessions.practice_server_enabled) — see ChampionshipPracticeService.
class PushChampionshipPractice extends Command
{
    protected $signature = 'championships:push-practice';

    protected $description = 'Pushes a 24h open practice session with the next round\'s track to each championship practice server';

    public function handle(ChampionshipPracticeService $practice): int
    {
        foreach ($practice->duePushes() as ['championship' => $championship, 'round' => $round, 'server' => $server]) {
            $error = $practice->push($championship, $round, $server);

            $error
                ? $this->error("{$championship->name}: {$error}")
                : $this->info("{$championship->name}: {$round->track} → {$server->name}");
        }

        return self::SUCCESS;
    }
}
