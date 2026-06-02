<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\PsnLookupService;
use Illuminate\Console\Command;

class ResolveIds extends Command
{
    protected $signature   = 'xcl:resolve-ids {--platform=ps5 : Platform to resolve (ps5)}';
    protected $description = 'Resolve gamertags to real platform account IDs and update users';

    public function handle(PsnLookupService $psn): int
    {
        $platform = $this->option('platform');

        // Users where platform_id is not yet a resolved numeric ID (e.g. still a gamertag)
        $users = User::where('platform', $platform)
            ->where(fn($q) => $q
                ->whereNull('platform_id')
                ->orWhereRaw("platform_id NOT REGEXP '^P[0-9]+'")
            )
            ->get();

        if ($users->isEmpty()) {
            $this->info('No users found that need ID resolution.');
            return self::SUCCESS;
        }

        $this->info("Resolving {$users->count()} {$platform} user(s)...");
        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        $resolved = 0;
        $failed   = 0;

        foreach ($users as $user) {
            // Gamertag is stored as platform_id without prefix, or falls back to name
            $gamertag = $user->platform_id
                ? ltrim($user->platform_id, 'Pp')
                : $user->name;

            try {
                $data = $psn->lookup($gamertag);

                $user->update([
                    'name'        => $data['onlineId'],
                    'platform_id' => 'P' . $data['accountId'],
                ]);

                $resolved++;
            } catch (\Throwable $e) {
                $this->newLine();
                $this->warn("  Failed [{$gamertag}]: {$e->getMessage()}");
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done — {$resolved} resolved, {$failed} failed.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}