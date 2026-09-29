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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

// Keeps a weekly Time Trial event's config on its server (XCL SERVER 6).
//
// The server can't be restarted remotely and only reads its config files when it restarts
// on its own, so nothing here depends on a restart: the files are simply uploaded again
// every hour (an upload never kicks anyone) and whichever restart comes next picks up the
// latest. For the same reason the entry list is open by default: it lists every member with
// a console ID, like the old site's daily upload, so anyone can join at any time. Who counts
// for the event is decided by the signups on the website (TimeTrialResultCollector). An
// event can instead use a forced list of only its signups (entryList()), re-uploaded
// within minutes of every signup change.
//
// The server loops a short practice and a qualifying session; ACC writes a result file at
// the end of each (…_FP.json, …_Q.json).
class TimeTrialServerService
{
    public function __construct(
        private readonly AccServerConfigService $raceConfig,
        private readonly FtpService $ftp,
    ) {}

    // The event each server should carry: the one live now, else the next one coming up.
    public function eventFor(FtpServer $server, CarbonInterface $now): ?TimeTrialEvent
    {
        return TimeTrialEvent::published()
            ->whereNull('finalized_at')
            ->where('ftp_server_id', $server->id)
            ->where('ends_at', '>', $now)
            ->orderBy('starts_at')
            ->first();
    }

    /** @return Collection<int, TimeTrialEvent> events whose upload is due now, one per server */
    public function duePushes(CarbonInterface $now): Collection
    {
        $every = (int) config('time_trials.push_every_minutes', 60);

        return FtpServer::withoutTenantScope()
            ->whereIn('id', TimeTrialEvent::published()->whereNull('finalized_at')->select('ftp_server_id'))
            ->where('active', true)
            ->get()
            ->filter(fn (FtpServer $server) => $server->supportsRaceGame('acc'))
            ->map(fn (FtpServer $server) => $this->eventFor($server, $now))
            ->filter(fn (?TimeTrialEvent $event) => $event
                && ($event->last_pushed_at === null || $event->last_pushed_at->lte($now->copy()->subMinutes($every - 1))))
            ->values();
    }

    // Uploads every config file and records the outcome on the event. Returns an error
    // message, or null when it worked.
    public function push(TimeTrialEvent $event): ?string
    {
        $server = $event->server;
        if (! $server) {
            return $this->recordPush($event, 'The event has no server.', null);
        }

        [$files, $entryCount] = $this->buildFiles($event, $server);

        return $this->recordPush($event, $this->upload($files, $server), $entryCount);
    }

    private function recordPush(TimeTrialEvent $event, ?string $error, ?int $entryCount): ?string
    {
        $event->update(['last_pushed_at' => now(), 'last_push_error' => $error]
            + ($error ? [] : ['last_entry_count' => $entryCount]));

        $error
            ? Log::error("Time Trial push failed for event #{$event->id}: {$error}")
            : Log::info("Time Trial push: event #{$event->id} ({$event->track}), {$entryCount} entries");

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
        $hour = $this->raceConfig->startHour(null);

        $config = $this->raceConfig->configuration($race, $server);
        $config['sessions'] = [
            [
                'hourOfDay' => $hour,
                'dayOfWeekend' => 2,
                'timeMultiplier' => 1,
                'sessionType' => 'P',
                'sessionDurationMinutes' => (int) config('time_trials.practice_minutes', 2),
            ],
            [
                'hourOfDay' => $hour,
                'dayOfWeekend' => 3,
                'timeMultiplier' => 1,
                'sessionType' => 'Q',
                'sessionDurationMinutes' => (int) config('time_trials.qualifying_minutes', 30),
            ],
        ];
        // The same dry conditions all week, so every session is comparable.
        $config['rain'] = 0.0;
        $config['weatherRandomness'] = 0;

        $entryList = $this->entryList($event);

        return [[
            'event.json' => json_encode($config, JSON_PRETTY_PRINT),
            'settings.json' => json_encode($this->raceConfig->settings($race, $server), JSON_PRETTY_PRINT),
            'eventrules.json' => json_encode(
                array_merge($this->raceConfig->eventRules($race, $server), PracticeServerConfigService::PRACTICE_EVENT_RULES),
                JSON_PRETTY_PRINT
            ),
            'assistrules.json' => json_encode($this->raceConfig->assistRules($server), JSON_PRETTY_PRINT),
            // Compact: the open list holds every member, and the server ignores formatting.
            'entrylist.json' => json_encode($entryList, JSON_UNESCAPED_UNICODE),
        ], count($entryList['entries'])];
    }

    // Open (default): every member with a console ID (no grid fillers or suspended accounts),
    // so they show with their XCL name and number, and anyone can join.
    // Forced (event->forced_entry_list): only the signups can join. That only helps if the
    // server reloads the entry list without a restart, which is still to be tested.
    public function entryList(TimeTrialEvent $event): array
    {
        $entries = [];
        $add = function ($users) use (&$entries) {
            foreach ($users as $user) {
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
        };

        if ($event->forced_entry_list) {
            $add($event->drivers()->with('connectedAccounts')->orderBy('time_trial_registrations.id')->get());
        } else {
            User::where('is_filler', false)
                ->where('is_suspended', false)
                ->where(fn ($q) => $q->where('platform_id', 'like', 'M%')->orWhere('platform_id', 'like', 'P%'))
                ->orderBy('id')
                ->chunk(1000, $add);
        }

        AccServerConfigService::assignUniqueRaceNumbers($entries);

        return ['entries' => $entries, 'configVersion' => 1, 'forceEntryList' => $event->forced_entry_list ? 1 : 0];
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
