<?php

namespace App\Console\Commands;

use App\Models\Race;
use App\Services\AccServerConfigService;
use App\Services\FtpService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PushGPortalConfigs extends Command
{
    protected $signature   = 'gportal:push-configs';
    protected $description = 'Auto-push ACC server config to gPortal 10 minutes before a race slot';

    public function handle(AccServerConfigService $config, FtpService $ftp): void
    {
        $pushWindowStart = now()->subMinutes(2);
        $pushWindowEnd   = now()->addMinutes(12);

        $races = Race::whereNotNull('ftp_server_id')
            ->whereNotNull('slot_time')
            ->whereIn('config_push_status', ['pending', 'failed', null])
            ->whereBetween('slot_time', [$pushWindowStart, $pushWindowEnd])
            ->with('ftpServer')
            ->get();

        if ($races->isEmpty()) {
            return;
        }

        foreach ($races as $race) {
            $server = $race->ftpServer;
            if (!$server || !$server->active) {
                continue;
            }

            Log::info("gPortal auto-push: race #{$race->id} ({$race->title}) → {$server->name}");

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
                        : $config->configuration($race),
                    JSON_PRETTY_PRINT
                ),
                'settings.json' => json_encode(
                    $race->configFile('settings.json')
                        ? json_decode($race->configFile('settings.json'), true)
                        : $config->settings($race, $server),
                    JSON_PRETTY_PRINT
                ),
                'eventrules.json'  => json_encode($config->eventRules($server), JSON_PRETTY_PRINT),
                'assistrules.json' => json_encode($config->assistRules($server), JSON_PRETTY_PRINT),
            ];

            if (!$ftp->connect($server)) {
                Log::error("gPortal auto-push: could not connect to {$server->host}");
                $race->update(['config_push_status' => 'failed']);
                continue;
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
                Log::error("gPortal auto-push: failed files — " . implode(', ', $failed));
                $race->update(['config_push_status' => 'failed', 'config_pushed_at' => now()]);
            } else {
                Log::info("gPortal auto-push: success for race #{$race->id}");
                $race->update(['config_push_status' => 'pushed', 'config_pushed_at' => now()]);
            }
        }
    }
}
