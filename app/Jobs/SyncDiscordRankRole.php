<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\DiscordRoleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncDiscordRankRole implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $userId) {}

    public function handle(DiscordRoleService $discordRoleService): void
    {
        $user = User::find($this->userId);
        if ($user) {
            $discordRoleService->syncUser($user);
        }
    }
}
