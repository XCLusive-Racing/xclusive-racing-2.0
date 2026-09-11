<?php

namespace App\Jobs;

use App\Models\PracticeServerSession;
use App\Services\FtpService;
use App\Services\PracticeServer\PracticeServerConfigService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PushPracticeServerConfigJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 120, 300];

    public function __construct(public PracticeServerSession $session)
    {
    }

    public function handle(PracticeServerConfigService $configService): void
    {
        // PracticeServerConfigService also lazy-loads $race->ftpServer internally
        // (a race's own assigned server, distinct from the practice server's own) —
        // bypass that one too, same reasoning as practiceServer.ftpServer below.
        $session = $this->session->fresh([
            'race.ftpServer'           => fn ($q) => $q->withoutTenantScope(),
            'practiceServer.ftpServer' => fn ($q) => $q->withoutTenantScope(),
        ]);

        // Guard against double dispatch — if another worker already moved this past
        // "pushing" (or it was cancelled in the meantime), there's nothing to do.
        if (!$session || $session->status !== PracticeServerSession::STATUS_PUSHING) {
            return;
        }

        $race          = $session->race;
        $practiceServer = $session->practiceServer;
        $ftpServer      = $practiceServer->ftpServer;

        $entryListResult = $configService->entryList($race);

        $gap = $practiceServer->admissionGap();
        if ($entryListResult->entryCount > $gap) {
            Log::warning('Practice server entry count exceeds admission gap', [
                'session_id'   => $session->id,
                'race_id'      => $race->id,
                'entry_count'  => $entryListResult->entryCount,
                'gap'          => $gap,
            ]);
        }

        $files = [
            'event.json'       => json_encode($configService->configuration($race, $session), JSON_PRETTY_PRINT),
            'settings.json'    => json_encode($configService->settings($race, $practiceServer), JSON_PRETTY_PRINT),
            'eventrules.json'  => json_encode($configService->eventRules($race), JSON_PRETTY_PRINT),
            'assistrules.json' => json_encode($configService->assistRules($race), JSON_PRETTY_PRINT),
            'entrylist.json'   => json_encode($entryListResult->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        ];

        $ftp = new FtpService();

        if (!$ftp->connect($ftpServer)) {
            throw new \RuntimeException("Could not connect to practice server FTP ({$ftpServer->host}).");
        }

        $cfgPath = rtrim($ftpServer->cfg_path ?? '/cfg', '/');
        $failed  = [];

        foreach ($files as $filename => $content) {
            $tempName = $filename . '.' . Str::random(8) . '.tmp';

            if (!$ftp->uploadFile("{$cfgPath}/{$tempName}", $content)) {
                $failed[] = $filename;
                continue;
            }

            if (!$ftp->renameFile("{$cfgPath}/{$tempName}", "{$cfgPath}/{$filename}")) {
                $ftp->deleteFile("{$cfgPath}/{$tempName}");
                $failed[] = $filename;
            }
        }

        $ftp->disconnect();

        if ($failed) {
            throw new \RuntimeException('Failed to write: ' . implode(', ', $failed));
        }

        $session->update([
            'status'      => PracticeServerSession::STATUS_LIVE,
            'pushed_at'   => now(),
            'entry_count' => $entryListResult->entryCount,
            'last_error'  => null,
        ]);

        Log::info('Practice server config pushed', [
            'session_id'    => $session->id,
            'race_id'       => $race->id,
            'entry_count'   => $entryListResult->entryCount,
            'skipped_count' => $entryListResult->skippedCount,
            'pushed_at'     => now()->toIso8601String(),
        ]);
    }

    public function failed(\Throwable $e): void
    {
        $session = $this->session->fresh();

        if (!$session) {
            return;
        }

        $session->update([
            'status'     => PracticeServerSession::STATUS_FAILED,
            'last_error' => $e->getMessage(),
        ]);

        Log::error('Practice server config push failed permanently', [
            'session_id' => $session->id,
            'race_id'    => $session->race_id,
            'error'      => $e->getMessage(),
        ]);

        $webhook = config('services.discord.webhook_mrs_racewell');
        if ($webhook) {
            try {
                Http::timeout(5)->post($webhook, [
                    'content' => "⚠️ **Practice server config push failed** — session #{$session->id} (race #{$session->race_id}): {$e->getMessage()}",
                ]);
            } catch (\Throwable $notifyError) {
                Log::error('Practice server failure Discord notify failed: ' . $notifyError->getMessage());
            }
        }
    }
}
