<?php

namespace App\Services\PracticeServer;

use App\Models\PracticeServer;
use App\Models\PracticeServerSession;
use App\Models\Race;
use App\Services\AccServerConfigService;

// Builds the four ACC config files for a practice session, reusing AccServerConfigService
// for everything that should match the race exactly (track/weather derivation, assist
// rules, event rules) rather than re-deriving that logic separately.
class PracticeServerConfigService
{
    public function __construct(private readonly AccServerConfigService $raceConfig = new AccServerConfigService())
    {
    }

    // event.json — track/ambientTemp/trackTemp/cloudLevel/rain come from whatever the
    // race's own config would derive (same weather/time-of-day/track fields), the
    // sessions array is replaced with a single practice session spanning the full
    // window, and weatherRandomness is forced to zero so conditions stay representative.
    public function configuration(Race $race, PracticeServerSession $session): array
    {
        $base = $this->raceConfig->configuration($race, $race->ftpServer);

        $durationMinutes = max(1, $session->window_start->diffInMinutes($session->window_end));

        $base['sessions'] = [[
            'hourOfDay'              => $this->raceConfig->startHour($race->time_of_day),
            'dayOfWeekend'           => 2,
            'timeMultiplier'         => 1,
            'sessionType'            => 'P',
            'sessionDurationMinutes' => $durationMinutes,
        ]];

        $base['weatherRandomness'] = 0;

        return $base;
    }

    // settings.json — practice server's own capacity and an open, ungated session.
    public function settings(Race $race, PracticeServer $server): array
    {
        $base = $this->raceConfig->defaultSettings();

        return array_merge($base, [
            'serverName'                 => $race->title . ' - Practice',
            'password'                   => $server->join_password ?? '',
            'maxCarSlots'                => $server->max_car_slots,
            'maxConnections'             => $server->max_connections,
            'carGroup'                   => $this->raceConfig->carGroup($race->car_class),
            'safetyRatingRequirement'    => -1,
            'racecraftRatingRequirement' => -1,
            'trackMedalsRequirement'     => 0,
        ]);
    }

    // eventrules.json — same car-behaviour rules as the race (pit windows aside), with
    // every pit-stop/mandatory-stop/driver-swap rule explicitly stripped since none of
    // that applies to an open practice session.
    public function eventRules(Race $race): array
    {
        $base = $this->raceConfig->eventRules($race, $race->ftpServer);

        return array_merge($base, [
            'mandatoryPitstopCount'                => 0,
            'isRefuellingAllowedInRace'             => false,
            'isRefuellingTimeFixed'                 => false,
            'isMandatoryPitstopRefuellingRequired'  => false,
            'isMandatoryPitstopTyreChangeRequired'  => false,
            'isMandatoryPitstopSwapDriverRequired'  => false,
            'driverStintTimeSec'                    => -1,
            'maxTotalDrivingTime'                   => -1,
        ]);
    }

    // assistrules.json — copied as-is from whatever the race itself would run.
    public function assistRules(Race $race): array
    {
        return $this->raceConfig->assistRules($race->ftpServer);
    }

    // entrylist.json — a snapshot of confirmed signups at the moment of the push, not a
    // live query re-run later. forceEntryList is always 1, so only listed drivers can
    // occupy the limited practice slots. Signups without a valid platform_id are skipped
    // and counted rather than silently sent with a blank playerID.
    public function entryList(Race $race): PracticeEntryListResult
    {
        $registrations = $race->registrations()
            ->with(['user', 'teamEntry.team'])
            ->orderBy('team_entry_id')
            ->orderBy('created_at')
            ->get();

        $entries          = [];
        $skipped          = 0;
        $processedTeamIds = [];

        foreach ($registrations as $reg) {
            if ($reg->team_entry_id !== null) {
                if (isset($processedTeamIds[$reg->team_entry_id])) {
                    continue;
                }
                $processedTeamIds[$reg->team_entry_id] = true;

                $teamRegs  = $registrations->where('team_entry_id', $reg->team_entry_id);
                $teamEntry = $reg->teamEntry;

                $drivers = [];
                foreach ($teamRegs as $teamReg) {
                    $user = $teamReg->user;
                    if (empty($user?->platform_id)) {
                        $skipped++;
                        continue;
                    }
                    $drivers[] = [
                        'firstName'      => '',
                        'lastName'       => $user->name ?? '',
                        'shortName'      => mb_strtoupper(mb_substr(preg_replace('/\s+/', '', $user->name ?? ''), 0, 3)),
                        'playerID'       => $user->platform_id,
                        'driverCategory' => $user->ratingClass($race->game),
                    ];
                }

                if (empty($drivers)) {
                    continue; // whole car had no valid platform IDs
                }

                $carNumber = $teamEntry?->car_number ?? 0;

                $entries[] = [
                    'drivers'             => $drivers,
                    'raceNumber'          => is_numeric($carNumber) ? (int) $carNumber : 0,
                    'defaultGridPosition' => -1,
                    'ballastKg'           => 0,
                    'forcedCarModel'      => $teamEntry?->car_model ?? -1,
                    'overrideDriverInfo'  => 1,
                    'teamName'            => $teamEntry?->team?->name ?? '',
                ];
            } else {
                $user = $reg->user;

                if (empty($user?->platform_id)) {
                    $skipped++;
                    continue;
                }

                $entries[] = [
                    'drivers' => [[
                        'firstName'      => '',
                        'lastName'       => $user->name ?? '',
                        'shortName'      => mb_strtoupper(mb_substr(preg_replace('/\s+/', '', $user->name ?? ''), 0, 3)),
                        'playerID'       => $user->platform_id,
                        'driverCategory' => $user->ratingClass($race->game),
                    ]],
                    'raceNumber'          => is_numeric($user->car_number) ? (int) $user->car_number : 0,
                    'defaultGridPosition' => -1,
                    'ballastKg'           => 0,
                    'forcedCarModel'      => is_numeric($user->car_model) ? (int) $user->car_model : -1,
                    'overrideDriverInfo'  => 1,
                    'teamName'            => $user->team ?? '',
                ];
            }
        }

        return new PracticeEntryListResult(
            config: [
                'entries'        => $entries,
                'configVersion'  => 1,
                'forceEntryList' => 1,
            ],
            entryCount: count($entries),
            skippedCount: $skipped,
        );
    }
}
