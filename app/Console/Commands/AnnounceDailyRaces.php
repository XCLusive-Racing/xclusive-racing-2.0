<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\NormalizesDiscordRoleId;
use App\Models\Race;
use App\Models\TeamEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnnounceDailyRaces extends Command
{
    use NormalizesDiscordRoleId;

    protected $signature = 'races:announce-daily {--dry-run : Build the message and log it instead of posting to Discord} {--force : Post even if already announced today}';

    protected $description = 'Posts upcoming races in the next 24h to Discord';

    private const CACHE_KEY = 'races_announce_daily_last_run';

    public function handle(): int
    {
        $today = now('Europe/London')->toDateString();

        if (!$this->option('force') && !$this->option('dry-run') && Cache::get(self::CACHE_KEY) === $today) {
            $this->info("Already announced today ({$today}) — skipping. Use --force to post again.");
            return self::SUCCESS;
        }

        $start = now();
        $end   = now()->addDay();

        $races = Race::where('scheduled_at', '>=', $start)
            ->where('scheduled_at', '<', $end)
            ->where('status', '!=', 'finished')
            ->orderBy('scheduled_at')
            ->get();

        $teamEvents = TeamEvent::where('starts_at', '>=', $start)
            ->where('starts_at', '<', $end)
            ->orderBy('starts_at')
            ->get();

        $embed = $this->buildEmbed($races, $teamEvents);

        if ($this->option('dry-run')) {
            Log::info('races:announce-daily dry-run', ['embed' => $embed]);
            $this->info('Dry run — message built and logged instead of sent:');
            $this->line(json_encode($embed, JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $webhook = config('services.discord.announcer_webhook');
        $roleId  = $this->normalizeRoleId(config('services.discord.xcl_member_role_id'));

        if (!$webhook || !$roleId) {
            $this->error('DISCORD_ANNOUNCER_WEBHOOK / DISCORD_XCL_MEMBER_ROLE_ID are not configured.');
            return self::FAILURE;
        }

        $response = Http::post($webhook, [
            'content' => "<@&{$roleId}>",
            'embeds'  => [$embed],
            'allowed_mentions' => [
                'roles' => [$roleId],
            ],
        ]);

        if (!$response->successful()) {
            Log::error('races:announce-daily webhook failed', ['status' => $response->status(), 'body' => $response->body()]);
            $this->error('Discord webhook failed: ' . $response->status());
            return self::FAILURE;
        }

        Cache::put(self::CACHE_KEY, $today, now()->addDay());
        $this->info('Daily race announcement posted to Discord.');

        return self::SUCCESS;
    }

    private function buildEmbed($races, $teamEvents): array
    {
        $fields = [];

        foreach ($races as $race) {
            $bits = ['🕐 ' . $race->scheduledAtUk()->format('g:iA') . ' BST', '🎮 ' . $this->gameLabel($race->game)];

            if ($race->sr_requirement) {
                $bits[] = '🛡️ SR ' . $race->sr_requirement . '.0+';
            }

            $fields[] = [
                'name'   => "🏎️ {$race->title} — {$race->track}",
                'value'  => implode("\n", $bits),
                'inline' => false,
            ];
        }

        foreach ($teamEvents as $event) {
            $name = $event->subtitle ? "⭐ {$event->title} — {$event->subtitle}" : "⭐ {$event->title}";
            $bits = ['🕐 ' . $event->starts_at->timezone('Europe/London')->format('g:iA') . ' BST'];

            $driverCount = $event->participatingDrivers()->count();
            if ($driverCount > 0) {
                $bits[] = "👥 {$driverCount} " . ($driverCount === 1 ? 'driver' : 'drivers');
            }

            $fields[] = [
                'name'   => $name,
                'value'  => implode("\n", $bits),
                'inline' => false,
            ];
        }

        $description = $fields
            ? 'Here are all the events happening in the next 24 hours!'
            : 'No scheduled events in the next 24 hours. Check back tomorrow!';

        return [
            'title'       => "🏁 Today's Race Schedule",
            'description' => $description,
            'color'       => 8134621, // #7c1fdd
            'fields'      => $fields,
            'footer'      => ['text' => 'XCLusive Racing • xclusiveracing.com'],
            'timestamp'   => now()->toIso8601String(),
        ];
    }

    private function gameLabel(string $game): string
    {
        return match ($game) {
            'acc'     => 'ACC',
            'lmu'     => 'Le Mans Ultimate',
            'iracing' => 'iRacing',
            'ac'      => 'ACC PC',
            default   => strtoupper($game),
        };
    }
}
