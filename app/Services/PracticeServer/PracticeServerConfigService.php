<?php

namespace App\Services\PracticeServer;

use App\Models\PracticeServer;
use App\Models\PracticeServerSession;
use App\Models\Race;
use App\Models\User;
use App\Services\AccServerConfigService;

// Builds the four ACC config files for a practice session, reusing AccServerConfigService
// for everything that should match the race exactly (track/weather derivation, assist
// rules, event rules) rather than re-deriving that logic separately.
class PracticeServerConfigService
{
    public function __construct(private readonly AccServerConfigService $raceConfig = new AccServerConfigService) {}

    // event.json — track/ambientTemp/trackTemp/cloudLevel/rain come from whatever the
    // race's own config would derive (same weather/time-of-day/track fields), the
    // sessions array is replaced with a single practice session spanning the full
    // window, and weatherRandomness is forced to zero so conditions stay representative.
    public function configuration(Race $race, PracticeServerSession $session): array
    {
        $base = $this->raceConfig->configuration($race, $race->ftpServer);

        $durationMinutes = max(1, $session->window_start->diffInMinutes($session->window_end));

        $base['sessions'] = [[
            'hourOfDay' => $this->raceConfig->startHour($race->time_of_day),
            'dayOfWeekend' => 2,
            'timeMultiplier' => (int) ($race->practice_time_multiplier ?: 1),
            'sessionType' => 'P',
            'sessionDurationMinutes' => $durationMinutes,
        ]];

        $base['weatherRandomness'] = 0;

        return $base;
    }

    // settings.json — practice server's own capacity and an open, ungated session.
    // serverName is left untouched (whatever the FTP server's own settings_defaults or the
    // built-in default already has) — practice pushes shouldn't rename the server per race.
    // maxConnections is likewise left at the base/default (always the platform maximum,
    // same as a normal race server) rather than the server's own max_connections column —
    // connections are never meant to be the limiting factor, only maxCarSlots is.
    public function settings(Race $race, PracticeServer $server): array
    {
        $base = $server->ftpServer?->settings_defaults ?? $this->raceConfig->defaultSettings();

        return array_merge($base, [
            'password' => $server->join_password ?? '',
            'maxCarSlots' => $server->max_car_slots,
            'carGroup' => $this->raceConfig->carGroup($race->car_class),
            'safetyRatingRequirement' => -1,
            'racecraftRatingRequirement' => -1,
            'trackMedalsRequirement' => 0,
            'isCrossplayServer' => $this->raceConfig->crossplayFlag($race, $server->ftpServer, $base),
        ]);
    }

    // How many entries beyond maxCarSlots could still connect (as spectators/queued) before
    // hitting maxConnections — computed from the settings this push actually sends, not the
    // server's stored max_connections column, since that's no longer what gets pushed.
    public function admissionGap(Race $race, PracticeServer $server): int
    {
        $settings = $this->settings($race, $server);

        return max(0, ($settings['maxConnections'] ?? 0) - ($settings['maxCarSlots'] ?? 0));
    }

    // eventrules.json — same car-behaviour rules as the race (pit windows aside), with
    // every pit-stop/mandatory-stop/driver-swap rule explicitly stripped since none of
    // that applies to an open practice session.
    public function eventRules(Race $race): array
    {
        $base = $this->raceConfig->eventRules($race, $race->ftpServer);

        return array_merge($base, [
            'mandatoryPitstopCount' => 0,
            'isRefuellingAllowedInRace' => false,
            'isRefuellingTimeFixed' => false,
            'isMandatoryPitstopRefuellingRequired' => false,
            'isMandatoryPitstopTyreChangeRequired' => false,
            'isMandatoryPitstopSwapDriverRequired' => false,
            'driverStintTimeSec' => -1,
            'maxTotalDrivingTime' => -1,
        ]);
    }

    // assistrules.json — copied as-is from whatever the race itself would run.
    public function assistRules(Race $race): array
    {
        return $this->raceConfig->assistRules($race->ftpServer);
    }

    // Builds every file the FTP push sends (as pretty-printed JSON, exactly as written to
    // disk) plus the entry-list result, so the push job and the admin preview always agree.
    public function buildFiles(Race $race, PracticeServerSession $session, PracticeServer $server): array
    {
        $entryListResult = $this->entryList($race);

        $files = [
            'event.json' => json_encode($this->configuration($race, $session), JSON_PRETTY_PRINT),
            'settings.json' => json_encode($this->settings($race, $server), JSON_PRETTY_PRINT),
            'eventrules.json' => json_encode($this->eventRules($race), JSON_PRETTY_PRINT),
            'assistrules.json' => json_encode($this->assistRules($race), JSON_PRETTY_PRINT),
            'entrylist.json' => json_encode($entryListResult->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        ];

        return [$files, $entryListResult];
    }

    // entrylist.json — a snapshot of confirmed signups at the moment of the push, not a
    // live query re-run later. forceEntryList is always 1, so only listed drivers can
    // occupy the limited practice slots. Signups without a valid player ID for the race's
    // game (User::playerIdFor() -- a Steam ID for ACC PC) are skipped and counted rather
    // than silently sent with a blank playerID.
    //
    // Deliberately includes waitlisted registrations, unlike
    // AccServerConfigService::entryList() (the real race server) -- someone waiting for
    // a seat should still be able to practice the track/setup in the meantime, they
    // just don't get to actually start the race until a spot opens up for them.
    public function entryList(Race $race): PracticeEntryListResult
    {
        $registrations = $race->registrations()
            ->with(['user.ownedRacingTeams', 'user.racingTeams', 'user.connectedAccounts', 'teamEntry.team'])
            ->orderBy('team_entry_id')
            ->orderBy('created_at')
            ->get();

        $entries = [];
        $skipped = 0;
        $processedTeamIds = [];

        foreach ($registrations as $reg) {
            if ($reg->team_entry_id !== null) {
                if (isset($processedTeamIds[$reg->team_entry_id])) {
                    continue;
                }
                $processedTeamIds[$reg->team_entry_id] = true;

                $teamRegs = $registrations->where('team_entry_id', $reg->team_entry_id);
                $teamEntry = $reg->teamEntry;

                $drivers = [];
                foreach ($teamRegs as $teamReg) {
                    $user = $teamReg->user;
                    $playerId = $user?->playerIdFor($race->game);
                    if (! $playerId) {
                        $skipped++;

                        continue;
                    }
                    $drivers[] = [
                        'firstName' => '',
                        'lastName' => AccServerConfigService::entryLastName($user, $teamEntry?->team?->name ?? ''),
                        'shortName' => mb_strtoupper(mb_substr(preg_replace('/\s+/', '', $user->name ?? ''), 0, 3)),
                        'playerID' => $playerId,
                        'driverCategory' => $user->ratingClass($race->game),
                    ];
                }

                if (empty($drivers)) {
                    continue; // whole car had no valid player IDs
                }

                $carNumber = $teamEntry?->car_number ?? 0;

                $entries[] = [
                    'drivers' => $drivers,
                    'raceNumber' => is_numeric($carNumber) ? (int) $carNumber : 0,
                    'defaultGridPosition' => -1,
                    'ballastKg' => 0,
                    'forcedCarModel' => $teamEntry?->car_model ?? -1,
                    'overrideDriverInfo' => 1,
                ];
            } else {
                $user = $reg->user;
                $playerId = $user?->playerIdFor($race->game);

                if (! $playerId) {
                    $skipped++;

                    continue;
                }

                $entries[] = [
                    'drivers' => [[
                        'firstName' => '',
                        'lastName' => AccServerConfigService::entryLastName($user),
                        'shortName' => mb_strtoupper(mb_substr(preg_replace('/\s+/', '', $user->name ?? ''), 0, 3)),
                        'playerID' => $playerId,
                        'driverCategory' => $user->ratingClass($race->game),
                    ]],
                    'raceNumber' => is_numeric($user->car_number) ? (int) $user->car_number : 0,
                    'defaultGridPosition' => -1,
                    'ballastKg' => 0,
                    'forcedCarModel' => is_numeric($user->car_model) ? (int) $user->car_model : -1,
                    'overrideDriverInfo' => 1,
                ];
            }
        }

        return new PracticeEntryListResult(
            config: [
                'entries' => $entries,
                'configVersion' => 1,
                'forceEntryList' => 1,
            ],
            entryCount: count($entries),
            skippedCount: $skipped,
        );
    }
}
