<?php

namespace App\Services\PracticeServer;

use App\Models\Championship;
use App\Models\FtpServer;
use App\Models\Race;
use App\Services\AccServerConfigService;
use App\Services\FtpService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

// A championship's 24h practice server (settings.sessions.practice_server_enabled):
// every midnight the server its next round runs on gets one open 24-hour practice
// session on that round's track — no entry list. Unlike the per-event practice server
// (PracticeServerSessionManager) there's no window to fit: the round's own config push
// (gportal:push-configs, 5 minutes before the start) simply takes over the server, and
// the next midnight after the round, "next round" is the following one.
class ChampionshipPracticeService
{
    public const SESSION_MINUTES = 24 * 60;

    public function __construct(
        private readonly AccServerConfigService $raceConfig,
        private readonly FtpService $ftp,
    ) {}

    // The rounds still to come, soonest first — the first one is the practice track.
    public function nextRound(Championship $championship): ?Race
    {
        return $championship->rounds()->reorder()
            ->where('scheduled_at', '>', now())
            ->whereNotIn('status', ['finished', 'cancelled', 'draft'])
            ->orderBy('scheduled_at')
            ->first();
    }

    // The server that round runs on, else the championship's default server — only if
    // it's active and runs the championship's game/platform.
    public function serverFor(Championship $championship, Race $round): ?FtpServer
    {
        $serverId = $round->ftp_server_id ?? $championship->ftp_server_id;
        $server = $serverId ? FtpServer::withoutTenantScope()->find($serverId) : null;

        return $server && $server->active && $server->supportsRaceGame($championship->game) ? $server : null;
    }

    // One push per server: championships sharing a server (and both wanting practice)
    // go to whichever has the soonest next round. Returns [championship, round, server].
    public function duePushes(): Collection
    {
        return Championship::withoutTenantScope()
            ->whereIn('status', Championship::PUBLIC_STATUSES)
            ->get()
            ->filter(fn (Championship $championship) => $championship->settings->sessions->practice_server_enabled ?? false)
            ->map(function (Championship $championship) {
                $round = $this->nextRound($championship);
                $server = $round ? $this->serverFor($championship, $round) : null;

                return $server ? ['championship' => $championship, 'round' => $round, 'server' => $server] : null;
            })
            ->filter()
            ->sortBy(fn (array $push) => $push['round']->scheduled_at)
            ->unique(fn (array $push) => $push['server']->id)
            ->values();
    }

    // Uploads the practice config and records the outcome on the championship.
    // Synchronous, like gportal:push-configs — it must not depend on a queue worker.
    // Returns an error message, or null when it worked.
    public function push(Championship $championship, Race $round, FtpServer $server): ?string
    {
        $error = $this->upload($this->buildFiles($round, $server), $server);

        Championship::withoutTenantScope()->whereKey($championship->id)->update([
            'practice_pushed_at' => now(),
            'practice_race_id' => $round->id,
            'practice_push_error' => $error,
        ]);

        $error
            ? Log::error("Championship practice push failed for championship #{$championship->id} ({$server->name}): {$error}")
            : Log::info("Championship practice push: championship #{$championship->id}, round #{$round->id} ({$round->track}) → {$server->name}");

        return $error;
    }

    private function upload(array $files, FtpServer $server): ?string
    {
        if (! $this->ftp->connect($server)) {
            return "Could not connect to {$server->host}:{$server->port}";
        }

        $cfgPath = rtrim($server->cfg_path ?? '/cfg', '/');
        $failed = [];
        foreach ($files as $filename => $content) {
            if (! $this->ftp->uploadConfigFile("{$cfgPath}/{$filename}", $content)) {
                $failed[] = $filename;
            }
        }

        $this->ftp->disconnect();

        return $failed ? 'Upload failed: '.implode(', ', $failed) : null;
    }

    // Every file the push writes, as the JSON written to disk.
    public function buildFiles(Race $round, FtpServer $server): array
    {
        $event = $this->raceConfig->configuration($round, $server);
        $event['sessions'] = [[
            'hourOfDay' => $this->raceConfig->startHour($round->time_of_day),
            'dayOfWeekend' => 2,
            'timeMultiplier' => 1,
            'sessionType' => 'P',
            'sessionDurationMinutes' => self::SESSION_MINUTES,
        ]];

        return [
            'event.json' => json_encode($event, JSON_PRETTY_PRINT),
            'settings.json' => json_encode($this->raceConfig->settings($round, $server), JSON_PRETTY_PRINT),
            'eventrules.json' => json_encode(
                array_merge($this->raceConfig->eventRules($round, $server), PracticeServerConfigService::PRACTICE_EVENT_RULES),
                JSON_PRETTY_PRINT
            ),
            'assistrules.json' => json_encode($this->raceConfig->assistRules($server), JSON_PRETTY_PRINT),
            // Open to everyone — and it has to be written: the previous round's forced
            // entry list would otherwise still be on the server, locking everyone else out.
            'entrylist.json' => json_encode(['entries' => [], 'forceEntryList' => 0], JSON_PRETTY_PRINT),
        ];
    }
}
