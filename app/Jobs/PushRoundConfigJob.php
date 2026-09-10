<?php

namespace App\Jobs;

use App\Models\Race;
use App\Services\Contracts\ServerConfigGenerator;
use App\Services\FtpService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Manual "push now" for a single round, triggered by a league manager for their own
// championship's round (Phase 3, docs/championships/PLAN.md) — unlike the scheduled
// gPortal push (PushGPortalConfigs, XCL-staff-operated, synchronous, runs every
// minute against every due race) this is one race, on demand, by a less-trusted
// user, so it goes through the queue for retry/reliability rather than blocking the
// request — the same reasoning PushPracticeServerConfigJob already established.
class PushRoundConfigJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 120, 300];

    public function __construct(public int $raceId)
    {
    }

    public function handle(ServerConfigGenerator $config, FtpService $ftp): void
    {
        $race = Race::with(['ftpServer' => fn ($q) => $q->withoutTenantScope()])->find($this->raceId);

        if (!$race || !$race->ftpServer || !$race->ftpServer->active) {
            return;
        }

        $server = $race->ftpServer;

        $freshSettings = $config->settings($race, $server);
        $settingsData  = $race->configFile('settings.json')
            ? array_merge(json_decode($race->configFile('settings.json'), true), [
                'password'   => $freshSettings['password'],
                'serverName' => $freshSettings['serverName'],
            ])
            : $freshSettings;

        $files = [
            'entrylist.json' => json_encode(
                $race->configFile('entrylist.json')
                    ? json_decode($race->configFile('entrylist.json'), true)
                    : $config->entryList($race),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            ),
            'event.json' => json_encode(
                $race->configFile('event.json')
                    ? json_decode($race->configFile('event.json'), true)
                    : $config->configuration($race, $server),
                JSON_PRETTY_PRINT
            ),
            'settings.json' => json_encode($settingsData, JSON_PRETTY_PRINT),
            'eventrules.json' => json_encode(
                $race->configFile('eventrules.json')
                    ? json_decode($race->configFile('eventrules.json'), true)
                    : $config->eventRules($race, $server),
                JSON_PRETTY_PRINT
            ),
            'assistrules.json' => json_encode($config->assistRules($server), JSON_PRETTY_PRINT),
        ];

        $invalid = [];
        foreach ($files as $filename => $content) {
            if ($content === false) {
                $invalid[] = $filename;
            }
        }

        if ($invalid) {
            $this->markFailed($race, 'Invalid JSON, push skipped: ' . implode(', ', $invalid));
            return;
        }

        if (!$ftp->connect($server)) {
            $this->markFailed($race, "Could not connect to {$server->host}:{$server->port}");
            return;
        }

        $cfgPath = rtrim($server->cfg_path ?? '/cfg', '/');
        $failed  = [];

        foreach ($files as $filename => $content) {
            if (!$ftp->uploadFile("{$cfgPath}/{$filename}", $content)) {
                $failed[] = $filename;
            }
        }

        $ftp->disconnect();

        if ($failed) {
            $this->markFailed($race, 'Upload failed: ' . implode(', ', $failed));
            return;
        }

        Log::info("Round config push: success for race #{$race->id}");

        Race::where('id', $race->id)->update([
            'config_push_status'   => 'pushed',
            'config_push_error'    => null,
            'config_pushed_at'     => now(),
            'config_push_attempts' => 0,
        ]);
    }

    private function markFailed(Race $race, string $error): void
    {
        Log::error("Round config push: {$error} for race #{$race->id}");

        Race::where('id', $race->id)->update([
            'config_push_status'   => 'failed',
            'config_push_error'    => $error,
            'config_pushed_at'     => now(),
            'config_push_attempts' => DB::raw('config_push_attempts + 1'),
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Race::where('id', $this->raceId)->update([
            'config_push_status' => 'failed',
            'config_push_error'  => $e->getMessage(),
            'config_pushed_at'   => now(),
        ]);

        $webhook = config('services.discord.webhook_mrs_racewell');
        if (!$webhook) {
            return;
        }

        try {
            Http::timeout(5)->post($webhook, [
                'content' => "⚠️ **Round config push failed** — race #{$this->raceId}: {$e->getMessage()}",
            ]);
        } catch (\Throwable $notifyError) {
            Log::error('Round config push failure Discord notify failed: ' . $notifyError->getMessage());
        }
    }
}
