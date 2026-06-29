<?php

namespace App\Console\Commands;

use App\Models\Race;
use App\Services\AccServerConfigService;
use App\Services\FtpService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PushGPortalConfigs extends Command
{
    protected $signature   = 'gportal:push-configs';
    protected $description = 'Auto-push ACC server config to gPortal before a race slot, with safety repush and retry logic';

    public function handle(AccServerConfigService $config, FtpService $ftp): void
    {
        $now = now();

        // Phase 1: normal push — pending/failed races, up to 30min before slot, retry until 10min after
        $normalRaces = Race::whereNotNull('ftp_server_id')
            ->whereNotNull('slot_time')
            ->whereIn('config_push_status', ['pending', 'failed', null])
            ->where('config_push_attempts', '<', 15)
            ->whereBetween('slot_time', [$now->copy()->subMinutes(30), $now->copy()->addMinutes(10)])
            ->with('ftpServer')
            ->get();

        // Phase 2: safety repush — already pushed, but within 2min of slot and last push was >5min ago
        // Catches server crashes/restarts between initial push and race start
        $safetyRaces = Race::whereNotNull('ftp_server_id')
            ->whereNotNull('slot_time')
            ->where('config_push_status', 'pushed')
            ->whereBetween('slot_time', [$now->copy()->subMinutes(2), $now->copy()->addMinutes(1)])
            ->where(function ($q) use ($now) {
                $q->whereNull('config_pushed_at')
                  ->orWhere('config_pushed_at', '<', $now->copy()->subMinutes(5));
            })
            ->with('ftpServer')
            ->get();

        $races = $normalRaces->merge($safetyRaces)->unique('id');

        if ($races->isEmpty()) {
            return;
        }

        foreach ($races as $race) {
            $server = $race->ftpServer;
            if (!$server || !$server->active) {
                continue;
            }

            $isSafetyPush = $race->config_push_status === 'pushed';
            $label        = $isSafetyPush ? 'safety-repush' : 'auto-push';

            Log::info("gPortal {$label}: race #{$race->id} ({$race->title}) → {$server->name}");

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
                        : $config->settings($race),
                    JSON_PRETTY_PRINT
                ),
            ];

            if (!$ftp->connect($server)) {
                $error = "Could not connect to {$server->host}:{$server->port}";
                Log::error("gPortal {$label}: {$error} for race #{$race->id}");

                Race::where('id', $race->id)->update([
                    'config_push_status'   => 'failed',
                    'config_push_error'    => $error,
                    'config_pushed_at'     => now(),
                    'config_push_attempts' => DB::raw('config_push_attempts + 1'),
                ]);
                continue;
            }

            $cfgPath = rtrim($server->cfg_path ?? '/cfg', '/');
            $failed  = [];

            foreach ($files as $filename => $content) {
                if (!$ftp->uploadFile("{$cfgPath}/{$filename}", $content)) {
                    $failed[] = $filename;
                }
            }

            // Verify the push by reading back event.json
            if (empty($failed)) {
                $verify = $ftp->getFileContent("{$cfgPath}/event.json");
                if ($verify === false || strlen(trim($verify)) < 10) {
                    $failed[] = 'event.json (verification read-back failed)';
                }
            }

            $ftp->disconnect();

            if ($failed) {
                $error = 'Upload failed: ' . implode(', ', $failed);
                Log::error("gPortal {$label}: {$error} for race #{$race->id}");

                Race::where('id', $race->id)->update([
                    'config_push_status'   => 'failed',
                    'config_push_error'    => $error,
                    'config_pushed_at'     => now(),
                    'config_push_attempts' => DB::raw('config_push_attempts + 1'),
                ]);
            } else {
                Log::info("gPortal {$label}: success for race #{$race->id}");

                Race::where('id', $race->id)->update([
                    'config_push_status'   => 'pushed',
                    'config_push_error'    => null,
                    'config_pushed_at'     => now(),
                    'config_push_attempts' => 0,
                ]);
            }
        }
    }
}
