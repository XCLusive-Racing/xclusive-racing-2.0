<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\NormalizesDiscordRoleId;
use App\Models\Report;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnnounceOpenReports extends Command
{
    use NormalizesDiscordRoleId;

    protected $signature = 'reports:announce-daily {--dry-run : Build the message and log it instead of posting to Discord} {--force : Post even if already announced today}';

    protected $description = 'Posts the count of open (pending/investigating) incident reports to Discord for stewards';

    private const CACHE_KEY = 'reports_announce_daily_last_run';

    public function handle(): int
    {
        $today = now('Europe/London')->toDateString();

        if (!$this->option('force') && !$this->option('dry-run') && Cache::get(self::CACHE_KEY) === $today) {
            $this->info("Already announced today ({$today}) — skipping. Use --force to post again.");
            return self::SUCCESS;
        }

        $pending       = Report::where('status', 'pending')->count();
        $investigating = Report::where('status', 'investigating')->count();
        $total         = $pending + $investigating;

        if ($total === 0) {
            $message = 'No open reports — nothing to announce.';
            if ($this->option('dry-run')) {
                Log::info('reports:announce-daily dry-run', ['message' => $message]);
            }
            $this->info($message);
            return self::SUCCESS;
        }

        $embed = $this->buildEmbed($pending, $investigating, $total);

        if ($this->option('dry-run')) {
            Log::info('reports:announce-daily dry-run', ['embed' => $embed]);
            $this->info('Dry run — message built and logged instead of sent:');
            $this->line(json_encode($embed, JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $webhook = config('services.discord.penalty_webhook');
        $roleId  = $this->normalizeRoleId(config('services.discord.steward_role_id'));

        if (!$webhook || !$roleId) {
            $this->error('DISCORD_PENALTY_WEBHOOK / DISCORD_STEWARD_ROLE_ID are not configured.');
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
            Log::error('reports:announce-daily webhook failed', ['status' => $response->status(), 'body' => $response->body()]);
            $this->error('Discord webhook failed: ' . $response->status());
            return self::FAILURE;
        }

        Cache::put(self::CACHE_KEY, $today, now()->addDay());
        $this->info("Open reports announcement posted to Discord ({$total} open).");

        return self::SUCCESS;
    }

    private function buildEmbed(int $pending, int $investigating, int $total): array
    {
        return [
            'title'       => '🚨 Open Incident Reports',
            'description' => "There " . ($total === 1 ? 'is 1 open report' : "are {$total} open reports") . " awaiting steward review.",
            'color'       => 8134621, // #7c1fdd
            'fields'      => [
                ['name' => '⏳ Pending',       'value' => (string) $pending,       'inline' => true],
                ['name' => '🔍 Investigating', 'value' => (string) $investigating, 'inline' => true],
            ],
            'url'         => route('admin.reports.index'),
            'footer'      => ['text' => 'XCLusive Racing • xclusiveracing.com'],
            'timestamp'   => now()->toIso8601String(),
        ];
    }
}
