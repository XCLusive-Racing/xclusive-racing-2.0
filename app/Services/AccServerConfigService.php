<?php

namespace App\Services;

use App\Models\Bop;
use App\Models\FtpServer;
use App\Models\Race;

class AccServerConfigService
{
    public function entryList(Race $race): array
    {
        $registrations = $race->registrations()
            ->with(['user', 'teamEntry'])
            ->orderBy('team_entry_id')
            ->orderBy('created_at')
            ->get();

        $entries           = [];
        $processedTeamIds  = [];

        foreach ($registrations as $reg) {
            if ($reg->team_entry_id !== null) {
                // Team entry — all drivers of the same car go into one entry
                if (isset($processedTeamIds[$reg->team_entry_id])) {
                    continue;
                }
                $processedTeamIds[$reg->team_entry_id] = true;

                $teamRegs  = $registrations->where('team_entry_id', $reg->team_entry_id);
                $teamEntry = $reg->teamEntry;
                $carNumber = $teamEntry?->car_number ?? 0;

                $drivers = $teamRegs->map(fn($tr) => [
                    'firstName'      => '',
                    'lastName'       => $tr->user->name ?? '',
                    'shortName'      => mb_strtoupper(mb_substr(preg_replace('/\s+/', '', $tr->user->name ?? ''), 0, 3)),
                    'playerID'       => $tr->user->platform_id ?? '',
                    'driverCategory' => $tr->user->ratingClass($race->game),
                ])->values()->all();

                $entries[] = [
                    'drivers'             => $drivers,
                    'raceNumber'          => is_numeric($carNumber) ? (int) $carNumber : 0,
                    'defaultGridPosition' => -1,
                    'ballastKg'           => 0,
                    'forcedCarModel'      => -1,
                    'overrideDriverInfo'  => 1,
                ];
            } else {
                // Solo driver
                $user      = $reg->user;
                $shortName = mb_strtoupper(mb_substr(preg_replace('/\s+/', '', $user->name ?? ''), 0, 3));

                $entries[] = [
                    'drivers' => [
                        [
                            'firstName'      => '',
                            'lastName'       => $user->name ?? '',
                            'shortName'      => $shortName,
                            'playerID'       => $user->platform_id ?? '',
                            'driverCategory' => $user->ratingClass($race->game),
                        ],
                    ],
                    'raceNumber'          => is_numeric($user->car_number) ? (int) $user->car_number : 0,
                    'defaultGridPosition' => -1,
                    'ballastKg'           => 0,
                    'forcedCarModel'      => -1,
                    'overrideDriverInfo'  => 1,
                ];
            }
        }

        return [
            'entries'        => $entries,
            'configVersion'  => 1,
            'forceEntryList' => 1,
        ];
    }

    public function configuration(Race $race, ?FtpServer $server = null): array
    {
        $defaults = $server?->event_defaults ?? $this->defaultEventConfig();

        // Practice shows the same in-game lighting as the Race, since that's what
        // drivers are actually setting up for. Qualifying is set an hour earlier —
        // sessions still run in real-world P → Q → R order regardless.
        $sessions = [];
        $hour     = $this->startHour($race->time_of_day);

        if ($race->practice_duration) {
            $sessions[] = [
                'hourOfDay'              => $hour,
                'dayOfWeekend'           => 2,
                'timeMultiplier'         => 1,
                'sessionType'            => 'P',
                'sessionDurationMinutes' => (int) $race->practice_duration,
            ];
        }

        if ($race->qualifying_duration) {
            $sessions[] = [
                'hourOfDay'              => max($hour - 1, 0),
                'dayOfWeekend'           => 3,
                'timeMultiplier'         => 1,
                'sessionType'            => 'Q',
                'sessionDurationMinutes' => (int) $race->qualifying_duration,
            ];
        }

        $sessions[] = [
            'hourOfDay'              => $hour,
            'dayOfWeekend'           => 3,
            'timeMultiplier'         => 1,
            'sessionType'            => 'R',
            'sessionDurationMinutes' => (int) ($race->race_duration ?? 20),
        ];

        if ($race->weather && $race->weather !== 'dry') {
            [$rain, $cloudLevel, $weatherRandomness] = $this->weatherParams($race->weather);
        } else {
            $rain              = $defaults['rain'] ?? 0.0;
            $cloudLevel        = $defaults['cloudLevel'] ?? 0.1;
            $weatherRandomness = $defaults['weatherRandomness'] ?? 1;
        }

        if ($race->rain_level !== null && in_array($race->weather, ['wet', 'mixed'], true)) {
            $rain = (float) $race->rain_level;
        }

        if ($race->weather_randomness !== null) {
            $wr = $race->weather_randomness;
            $weatherRandomness = $wr === 'random' ? rand(0, 7) : (int) $wr;
        }

        return [
            'track'                     => $this->trackSlug($race->track),
            'preRaceWaitingTimeSeconds' => $defaults['preRaceWaitingTimeSeconds'] ?? 120,
            'postQualySeconds'          => $defaults['postQualySeconds'] ?? 60,
            'postRaceSeconds'           => $defaults['postRaceSeconds'] ?? 180,
            'sessionOverTimeSeconds'    => $defaults['sessionOverTimeSeconds'] ?? 540,
            'ambientTemp'               => $race->ambient_temp ?? $defaults['ambientTemp'] ?? 20,
            'trackTemp'                 => $defaults['trackTemp'] ?? -1,
            'cloudLevel'                => $cloudLevel,
            'rain'                      => $rain,
            'weatherRandomness'         => $weatherRandomness,
            'sessions'                  => $sessions,
            'configVersion'             => 1,
        ];
    }

    public function settings(Race $race, ?FtpServer $server = null): array
    {
        $base = $server?->settings_defaults ?? $this->defaultSettings();

        $n = $server?->server_number;

        return array_merge($base, [
            'serverName'                 => $n
                ? 'XCL SERVER ' . $n . ' - Playstation 5 & Xbox Series S/X'
                : ($base['serverName'] ?? 'XCL SERVER - Playstation 5 & Xbox Series S/X'),
            'password'                   => $n ? $n . 'xcl' : ($base['password'] ?? '1xcl'),
            'safetyRatingRequirement'    => $this->srRequired($race),
            'racecraftRatingRequirement' => $this->rcRequired($race),
            'maxCarSlots'                => $race->max_drivers ?? ($base['maxCarSlots'] ?? 30),
            'carGroup'                   => $this->carGroup($race->car_class),
            'shortFormationLap'          => $this->shortFormationLap($race, $base),
        ]);
    }

    public function eventRules(?Race $race = null, ?FtpServer $server = null): array
    {
        $base = $server?->eventrules_defaults ?? $this->defaultEventRules();

        if ($race && $race->is_endurance) {
            $base = array_merge($base, [
                'driverStintTimeSec'                   => $race->driver_stint_time_mins ? $race->driver_stint_time_mins * 60 : -1,
                'maxTotalDrivingTime'                  => $race->max_total_driving_time_mins ? $race->max_total_driving_time_mins * 60 : -1,
                'isMandatoryPitstopSwapDriverRequired' => $race->mandatory_driver_swap,
            ]);
        }

        $fmt = $race?->eventFormat;

        if ($fmt) {
            $pitstopType  = $fmt->pitstop_type ?? 'none';
            $pitstopCount = (int) ($fmt->pitstop_count ?? 0);
            $minStopSecs  = $fmt->min_stop_secs;
        } elseif ($race && (int) ($race->pitstop_count ?? 0) > 0) {
            $pitstopType  = 'mandatory';
            $pitstopCount = (int) $race->pitstop_count;
            $minStopSecs  = $race->min_stop_secs;
        } else {
            return $base;
        }

        if ($pitstopType === 'none' || $pitstopCount === 0) {
            return array_merge($base, [
                'mandatoryPitstopCount'                => 0,
                'isRefuellingAllowedInRace'            => false,
                'isRefuellingTimeFixed'                => false,
                'isMandatoryPitstopRefuellingRequired' => false,
                'isMandatoryPitstopTyreChangeRequired' => false,
            ]);
        }

        $timeFixed = !empty($minStopSecs);

        return array_merge($base, [
            'mandatoryPitstopCount'                => $pitstopCount,
            'isRefuellingAllowedInRace'            => true,
            'isRefuellingTimeFixed'                => $timeFixed,
            'isMandatoryPitstopRefuellingRequired' => $timeFixed,
            'isMandatoryPitstopTyreChangeRequired' => false,
        ]);
    }

    public function assistRules(?FtpServer $server = null): array
    {
        return $server?->assistrules_defaults ?? $this->defaultAssistRules();
    }

    public function defaultEventConfig(): array
    {
        return [
            'preRaceWaitingTimeSeconds' => 120,
            'postQualySeconds'          => 60,
            'postRaceSeconds'           => 180,
            'sessionOverTimeSeconds'    => 540,
            'ambientTemp'               => 20,
            'trackTemp'                 => -1,
            'cloudLevel'                => 0.1,
            'rain'                      => 0,
            'weatherRandomness'         => 1,
        ];
    }

    public function defaultSettings(): array
    {
        return [
            'serverName'                 => 'XCL SERVER - Playstation 5 & Xbox Series S/X',
            'adminPassword'              => '3867cf9b',
            'randomizeTrackWhenEmpty'    => 0,
            'trackMedalsRequirement'     => 0,
            'safetyRatingRequirement'    => -1,
            'racecraftRatingRequirement' => -1,
            'allowAutoDQ'                => 0,
            'password'                   => '1xcl',
            'maxConnections'             => 120,
            'spectatorSlots'             => 2,
            'spectatorPassword'          => 'Password',
            'dumpLeaderboards'           => 1,
            'isCPServer'                 => 0,
            'competitionRatingMin'       => -1,
            'competitionRatingMax'       => -1,
            'configVersion'              => 1,
            'maxCarSlots'                => 30,
            'shortFormationLap'          => 0,
            'dumpEntryList'              => 1,
            'formationLapType'           => 3,
            'region'                     => 'EU',
            'isRaceLocked'               => 0,
            'isCrossplayServer'          => 1,
            'carGroup'                   => 'FreeForAll',
        ];
    }

    public function defaultEventRules(): array
    {
        return [
            'pitWindowLengthSec'                   => -1,
            'mandatoryPitstopCount'                => 1,
            'qualifyStandingType'                  => 1,
            'isRefuellingAllowedInRace'             => true,
            'isRefuellingTimeFixed'                => true,
            'isMandatoryPitstopRefuellingRequired' => true,
            'isMandatoryPitstopTyreChangeRequired' => false,
            'driverStintTimeSec'                   => -1,
            'maxTotalDrivingTime'                  => -1,
            'maxDriversCount'                      => 120,
            'isMandatoryPitstopSwapDriverRequired' => false,
        ];
    }

    public function defaultAssistRules(): array
    {
        return [
            'disableIdealLine'         => 0,
            'disableAutosteer'         => 1,
            'stabilityControlLevelMax' => 0,
            'disableAutoPitLimiter'    => 0,
            'disableAutoGear'          => 0,
            'disableAutoClutch'        => 0,
            'disableAutoEngineStart'   => 0,
            'disableAutoWiper'         => 0,
            'disableAutoLights'        => 0,
        ];
    }

    public function bop(string $game = 'acc'): array
    {
        $entries = Bop::where('game', $game)->where('active', true)->orderBy('car_model')->get();

        $mapped = [];
        foreach ($entries as $bop) {
            $carId = Bop::carModelId($bop->car_model);
            if ($carId === null) {
                continue;
            }

            $mapped[] = [
                'track'      => $bop->track ?? '',
                'carModel'   => $carId,
                'ballastKg'  => (int) $bop->ballast_kg,
                'restrictor' => (int) $bop->restrictor,
            ];
        }

        return [
            'entries'       => $mapped,
            'configVersion' => 1,
        ];
    }

    private function shortFormationLap(Race $race, array $base): int
    {
        $formationType = $race->eventFormat?->formation_type ?? '';

        // Format explicitly uses a short formation lap for all tracks.
        if (strtolower(trim($formationType)) === 'short') {
            return 1;
        }

        // Nordschleife always gets short regardless of format — a full lap takes too long.
        if ($this->trackSlug($race->track) === 'nurburgring_24h') {
            return 1;
        }

        return (int) ($base['shortFormationLap'] ?? 0);
    }

    private function srRequired(Race $race): int
    {
        return match ($race->sr_requirement) {
            '5'  => 5,
            '7'  => 7,
            default => -1,
        };
    }

    private function rcRequired(Race $race): int
    {
        return match ($race->min_rating) {
            'bronze'   => 0,
            'silver'   => 60,
            'gold'     => 80,
            'platinum' => 95,
            'alien'    => 99,
            default    => -1,
        };
    }

    private function startHour(?string $timeOfDay): int
    {
        if ($timeOfDay && preg_match('/^(\d{1,2}):(\d{2})$/', $timeOfDay, $m)) {
            return (int) $m[1];
        }

        return match ($timeOfDay) {
            'dusk'    => 17,
            'night'   => 21,
            'dynamic' => 10,
            default   => 14,
        };
    }

    private function weatherParams(?string $weather): array
    {
        // [rain, cloudLevel, weatherRandomness]
        return match ($weather) {
            'wet'    => [0.8, 0.8, 2],
            'mixed'  => [0.3, 0.5, 3],
            'random' => [0.0, 0.5, 4],
            default  => [0.0, 0.1, 1],
        };
    }

    // Tracks whose display name doesn't naively slugify into ACC's real internal track
    // folder name — Nordschleife is ACC's combined Nordschleife+GP endurance layout,
    // internally named "nurburgring_24h" (not "nordschleife"); Nürburgring's umlaut
    // breaks the naive regex slug below.
    private const TRACK_SLUG_OVERRIDES = [
        'Nordschleife' => 'nurburgring_24h',
        'Nürburgring'  => 'nurburgring',
    ];

    private function trackSlug(string $track): string
    {
        $track = trim($track);

        return self::TRACK_SLUG_OVERRIDES[$track]
            ?? strtolower(preg_replace('/[^a-z0-9_]/i', '_', $track));
    }

    private function carGroup(?string $carClass): string
    {
        if (!$carClass) return 'FreeForAll';

        return match (strtoupper(trim($carClass))) {
            'GT3'                              => 'GT3',
            'GT4'                              => 'GT4',
            'GT2'                              => 'GT2',
            'GTC'                              => 'GTC',
            'GTE'                              => 'GTE',
            'LMP2', 'LMP 2'                    => 'LMP2',
            'CUP', 'PORSCHE CUP', 'GT3 CUP'   => 'CUP',
            'ST', 'SUPER TROFEO'               => 'ST',
            'CHL', 'CHALLENGE'                 => 'CHL',
            'TCX', 'TCR', 'M2'                 => 'TCX',
            default                            => 'FreeForAll',
        };
    }
}