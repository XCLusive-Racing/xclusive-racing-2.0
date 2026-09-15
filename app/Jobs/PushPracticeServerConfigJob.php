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

        [$files, $entryListResult] = $configService->buildFiles($race, $session, $practiceServer);

        // Only entries beyond maxCarSlots draw on the connection headroom (the gap) — compare
        // that overflow, not the raw entry count, or this fires on almost every full session.
        $gap      = $configService->admissionGap($race, $practiceServer);
        $overflow = max(0, $entryListResult->entryCount - $practiceServer->max_car_slots);
        if ($overflow > $gap) {
            Log::warning('Practice server entry count exceeds admission gap', [
                'session_id'   => $session->id,
                'race_id'      => $race->id,
                'entry_count'  => $entryListResult->entryCount,
                'overflow'     => $overflow,
                'gap'          => $gap,
            ]);
        }

        $ftp = new FtpService();

        if (!$ftp->connect($ftpServer)) {
            throw new \RuntimeException("Could not connect to practice server FTP ({$ftpServer->host}).");
        }

        $cfgPath = rtrim($ftpServer->cfg_path ?? '/cfg', '/');
        $failed  = [];

        foreach ($files as $filename => $content) {
            $tempName = $filename . '.' . Str::random(8) . '.tmp';

            if (!$ftp->uploadFile("{$cfgPath}/{$tempName}", $content)) {
                $failed[] = "{$filename} ({$ftp->getLastError()})";
                continue;
            }

            if (!$ftp->renameFile("{$cfgPath}/{$tempName}", "{$cfgPath}/{$filename}")) {
                $renameError = $ftp->getLastError();
                $ftp->deleteFile("{$cfgPath}/{$tempName}");
                $failed[] = "{$filename} (rename: {$renameError})";
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
