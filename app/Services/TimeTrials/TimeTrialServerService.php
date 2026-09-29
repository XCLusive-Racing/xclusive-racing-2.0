<?php

namespace App\Services\TimeTrials;

use App\Models\FtpServer;
use App\Models\Race;
use App\Models\TimeTrialEvent;
use App\Models\User;
use App\Services\AccServerConfigService;
use App\Services\FtpService;
use App\Services\PracticeServer\PracticeServerConfigService;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

// Pushes a weekly Time Trial event to its server (XCL SERVER 6). The server restarts on
// its own cadence (reset_interval_minutes, every hour) and reads its config files on each
// restart, so the push goes up shortly before every restart: the config is written again
// every time and the entry list carries whoever signed up by then.
//
// The server runs a single practice session a few minutes shorter than the restart
// interval: ACC writes the session's result file (_FP.json) when the session ends, which
// has to happen before the restart cuts it off.
class TimeTrialServerService
{
    // How long before a restart the push may go up (the scheduler runs every 5 minutes).
    public const PUSH_LEAD_MINUTES = 10;

    // Minutes the session ends before the next restart, so its result file gets written.
    public const SESSION_MARGIN_MINUTES = 5;

    public function __construct(
        private readonly AccServerConfigService $raceConfig,
        private readonly FtpService $ftp,
    ) {}

    // The server's next restart after $now. Rolling servers restart every
    // reset_interval_minutes from reset_start_hour (UK wall clock, like isValidSlot()).
    public function nextRestart(FtpServer $server, CarbonInterface $now): Carbon
    {
        $interval = max(1, (int) ($server->reset_interval_minutes ?: 60));
        $local = Carbon::instance($now)->timezone('Europe/London');
        $restart = $local->copy()->startOfDay()->addHours((int) $server->reset_start_hour)->subDay();

        while ($restart->lte($local)) {
            $restart->addMinutes($interval);
        }

        return $restart->utc();
    }

    public function sessionMinutes(FtpServer $server): int
    {
        return max(10, (int) ($server->reset_interval_minutes ?: 60) - self::SESSION_MARGIN_MINUTES);
    }

    /** @return Collection<int, array{event: TimeTrialEvent, restart: Carbon}> events whose push is due now */
    public function duePushes(CarbonInterface $now): Collection
    {
        return TimeTrialEvent::published()
            ->whereNull('finalized_at')
            ->whereNotNull('ftp_server_id')
            ->where('ends_at', '>', $now)
            ->with('server')
            ->get()
            ->map(function (TimeTrialEvent $event) use ($now) {
                $server = $event->server;
                if (! $server || ! $server->active || ! $server->supportsRaceGame('acc')) {
                    return null;
                }

                $restart = $this->nextRestart($server, $now);
                $due = $now->gte($restart->copy()->subMinutes(self::PUSH_LEAD_MINUTES))
                    && $event->starts_at->lte($restart) && $event->ends_at->gt($restart)
                    && ! $event->last_pushed_for?->eq($restart);

                return $due ? ['event' => $event, 'restart' => $restart] : null;
            })
            ->filter()
            ->values();
    }

    // Uploads every config file and records the outcome on the event. Returns an error
    // message, or null when it worked.
    public function push(TimeTrialEvent $event, Carbon $restart): ?string
    {
        $server = $event->server;
        if (! $server) {
            return $this->recordPush($event, $restart, 'The event has no server.', null);
        }

        [$files, $entryCount] = $this->buildFiles($event, $server);

        return $this->recordPush($event, $restart, $this->upload($files, $server), $entryCount);
    }

    private function recordPush(TimeTrialEvent $event, Carbon $restart, ?string $error, ?int $entryCount): ?string
    {
        $event->update([
            'last_pushed_at' => now(),
            'last_push_error' => $error,
        ] + ($error ? [] : ['last_pushed_for' => $restart, 'last_entry_count' => $entryCount]));

        $error
            ? Log::error("Time Trial push failed for event #{$event->id}: {$error}")
            : Log::info("Time Trial push: event #{$event->id} ({$event->track}), {$entryCount} entries, for the restart at {$restart->toIso8601String()}");

        return $error;
    }

    // Written to a temp name and renamed, so a restart never reads a half-written file.
    private function upload(array $files, FtpServer $server): ?string
    {
        if (! $this->ftp->connect($server)) {
            return "Could not connect to {$server->host}:{$server->port}";
        }

        $cfgPath = rtrim($server->cfg_path ?? '/cfg', '/');
        $failed = [];
        foreach ($files as $filename => $content) {
            $temp = "{$cfgPath}/{$filename}.".Str::random(8).'.tmp';
            if (! $this->ftp->uploadConfigFile($temp, $content)) {
                $failed[] = $filename;

                continue;
            }
            if (! $this->ftp->renameFile($temp, "{$cfgPath}/{$filename}")) {
                $this->ftp->deleteFile($temp);
                $failed[] = "{$filename} (rename)";
            }
        }

        $this->ftp->disconnect();

        return $failed ? 'Upload failed: '.implode(', ', $failed) : null;
    }

    /** @return array{0: array<string, string>, 1: int} filename => JSON, and the entry count */
    public function buildFiles(TimeTrialEvent $event, FtpServer $server): array
    {
        $race = $this->race($event);

        $config = $this->raceConfig->configuration($race, $server);
        $config['sessions'] = [[
            'hourOfDay' => $this->raceConfig->startHour(null),
            'dayOfWeekend' => 2,
            'timeMultiplier' => 1,
            'sessionType' => 'P',
            'sessionDurationMinutes' => $this->sessionMinutes($server),
        ]];
        // The same dry conditions all week, so every hour is comparable.
        $config['rain'] = 0.0;
        $config['weatherRandomness'] = 0;

        $entryList = $this->entryList($event);

        return [[
            'event.json' => json_encode($config, JSON_PRETTY_PRINT),
            'settings.json' => json_encode($this->settings($event, $server), JSON_PRETTY_PRINT),
            'eventrules.json' => json_encode(
                array_merge($this->raceConfig->eventRules($race, $server), PracticeServerConfigService::PRACTICE_EVENT_RULES),
                JSON_PRETTY_PRINT
            ),
            'assistrules.json' => json_encode($this->raceConfig->assistRules($server), JSON_PRETTY_PRINT),
            'entrylist.json' => json_encode($entryList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        ], count($entryList['entries'])];
    }

    public function settings(TimeTrialEvent $event, FtpServer $server): array
    {
        return $this->raceConfig->settings($this->race($event), $server);
    }

    // Only signed-up drivers can join (forced entry list); each picks their own car in game,
    // limited to the event's class by the server's carGroup.
    public function entryList(TimeTrialEvent $event): array
    {
        $entries = [];
        foreach ($event->drivers()->with('connectedAccounts')->orderBy('time_trial_registrations.id')->get() as $user) {
            /** @var User $user */
            $playerId = $user->playerIdFor('acc');
            if (! $playerId) {
                continue;
            }

            $entries[] = [
                'drivers' => [[
                    'firstName' => '',
                    'lastName' => AccServerConfigService::entryLastName($user),
                    'shortName' => AccServerConfigService::entryShortName($user),
                    'playerID' => $playerId,
                    'driverCategory' => $user->ratingClass('acc'),
                ]],
                'raceNumber' => is_numeric($user->car_number) ? (int) $user->car_number : null,
                'defaultGridPosition' => -1,
                'ballastKg' => 0,
                'forcedCarModel' => -1,
                'overrideDriverInfo' => 1,
            ];
        }

        AccServerConfigService::assignUniqueRaceNumbers($entries);

        return ['entries' => $entries, 'configVersion' => 1, 'forceEntryList' => 1];
    }

    // An unsaved Race carrying the event's track and class, so the race config builder
    // (track naming, server name and password, class carGroup, assists) is reused as is.
    private function race(TimeTrialEvent $event): Race
    {
        return new Race([
            'title' => $event->displayTitle(),
            'game' => 'acc',
            'track' => $event->trackName(),
            'car_class' => $event->car_class,
            'weather' => 'dry',
        ]);
    }
}
