<?php

namespace App\Console\Commands\Discord;

use App\Models\User;
use App\Services\DiscordRoleService;
use Illuminate\Console\Command;

class SyncDiscordRanksCommand extends Command
{
    protected $signature = 'xcl:discord:sync-ranks';
    protected $description = 'Sync every Discord-linked user\'s rank role to match their current XCL rating (bulk rollout / safety-net sweep)';

    public function handle(DiscordRoleService $discordRoleService): int
    {
        $users = User::whereHas('connectedAccounts', fn ($q) => $q->where('provider', 'discord'))->get();

        if ($users->isEmpty()) {
            $this->info('No users with a linked Discord account found.');
            return 0;
        }

        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        foreach ($users as $user) {
            $discordRoleService->syncUser($user);
            usleep(300_000); // spread requests out to stay under Discord's per-route rate limit
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Synced {$users->count()} Discord-linked users.");

        return 0;
    }
}
